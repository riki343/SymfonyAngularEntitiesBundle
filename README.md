# SymfonyAngularEntitiesBundle
**Author**: Vladislav Kosko (riki34)
**Email**:  vladislav.kosko@gmail.com
**Skype**:  vladislav.kosko


### Installation

`composer require riki34/symfony-angular-entities`

### How to use it

`php app/console entity:convert <angular-module> <symfony-namespace>`

- `<angular-module>`    - this is your angular module where you want to convert entities.
- `<symfony-namespace>` - this is your symfony namespace where your entites placed.

**Example** : `php app/console entity:convert core riki34\AppBundle\Entity`

**All entities will be generated in `web/entities` directory.**
