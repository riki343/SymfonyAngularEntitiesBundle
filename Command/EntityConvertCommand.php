<?php

namespace riki34\SymfonyAngularEntitiesBundle\Command;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class EntityConvertCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('entity:convert')
            ->addArgument('module')
            ->addArgument('namespace')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $namespace = $input->getArgument('namespace');
        $module = $input->getArgument('module');

        $output->writeln(sprintf('Convering entities from namespace %s for module %s...', $namespace, $module));

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $meta = $em->getMetadataFactory()->getAllMetadata();
        /** @var ClassMetadata $m */
        foreach ($meta as $m) {
            $classNamespace = str_replace('\\', '', $m->getReflectionClass()->getNamespaceName());
            if ($classNamespace === $namespace) {
                $entity = explode('\\', $m->getName());
                $entity = $entity[count($entity) - 1];
                $output->writeln(sprintf("Creating class $entity.js"));
                $structure = $this->generateHeader($module, $entity);
                $structure = array_merge($structure, $this->generateConstructor($entity, $m));
                $structure = array_merge($structure, $this->generateObjectFunctions($entity));
                $structure = array_merge($structure, $this->generateStaticFunctions($entity));
                $structure = array_merge($structure, $this->generateFooter($entity));
                $this->writeFile($entity, $this->generateCode($structure));
            }
        }
    }

    private function generateHeader($module, $entity) {
        $fields = [];
        $fields[] = "(function (angular) {\n";
        $fields[] = sprintf("\tangular.module('%s').factory('%s', %sFactory);\n\n", $module, $entity, $entity);
        $fields[] = sprintf("\t%sFactory.\$inject = [];\n\n", $entity);
        $fields[] = sprintf("\tfunction %sFactory() {\n", $entity);

        return $fields;
    }

    /**
     * @param $entity
     * @param ClassMetadata $classMetadata
     * @return array
     */
    private function generateConstructor($entity, $classMetadata) {
        $fields = [];
        $fields[] = sprintf("\t\tfunction %s(data) {\n", $entity);
        $fields[] = "\t\tif (angular.isDefined(data)) {\n";
        foreach ($classMetadata->getFieldNames() as $item) {
            $item = $this->handleField($item, $classMetadata->getName());
            if ($item !== null) {
                $fields[] = $item;
            }
        }
        $fields[] = "\t\t} else {\n";

        $fields[] = "\t\t}\n\n";

        return $fields;
    }

    private function generateObjectFunctions($entity) {
        $fields = [];
        $fields[] = sprintf("\t\t%s.prototype.save = function () {\n", $entity);
        $fields[] = "\n";
        $fields[] = "\t\t};\n\n";

        $fields[] = sprintf("\t\t%s.prototype.delete = function () {\n", $entity);
        $fields[] = "\n";
        $fields[] = "\t\t};\n\n";

        return $fields;
    }

    /**
     * @param string $entity
     * @return array
     */
    private function generateStaticFunctions($entity) {
        $fields = [];
        $fields[] = sprintf("\t\t%s.create = function() {\n", $entity);
        $fields[] = "\n";
        $fields[] = "\t\t};\n\n";

        $fields[] = sprintf("\t\t%s.get = function() {\n", $entity);
        $fields[] = "\n";
        $fields[] = "\t\t};\n\n";

        return $fields;
    }

    private function generateFooter($entity) {
        $fields = [];
        $fields[] = sprintf("\t\treturn (%s);\n", $entity);
        $fields[] = "\t}\n";
        $fields[] = "})(angular);";
        return $fields;
    }

    private function generateCode($array) {
        $code = '';
        foreach ($array as $item) {
            $code .= $item;
        }

        return $code;
    }

    /**
     * @param string $field
     * @param string $class
     * @return string
     */
    function handleField($field, $class, $defined) {
        $annotationsReader = $this->getContainer()->get('annotation_reader');
        $reflectionProperty = new \ReflectionProperty($class, $field);
        $columnAnnotation = $annotationsReader->getPropertyAnnotation($reflectionProperty, 'Doctrine\ORM\Mapping\Column');
        if ($columnAnnotation !== null) {
            if ($defined) {
                return "\t\t\t\tthis.$field = data.$field;\n";
            } else {
                switch($columnAnnotation->type) {
                    case 'integer':     return "\t\t\t\tthis.$field = 0;\n";
                    case 'string':      return "\t\t\t\tthis.$field = '';\n";
                    case 'text':        return "\t\t\t\tthis.$field = '';\n";
                    case 'boolean':     return "\t\t\t\tthis.$field = false;\n";
                    case 'array':       return "\t\t\t\tthis.$field = [];\n";
                    case 'datetime':    return "\t\t\t\tthis.$field = new Date();\n";
                    case 'date':        return "\t\t\t\tthis.$field = new Date();\n";
                }
            }
        }

        return null;
    }

    private function writeFile($enity, $code) {
        $fs = new Filesystem();
        $fs->dumpFile('web/entities/' . $enity . '.js', $code);
    }
}
