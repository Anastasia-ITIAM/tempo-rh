## 1. Installation


### Installation de tout le package de tests & code quality en une ligne :
````
composer require --dev phpstan/phpstan phpstan/phpstan-symfony phpstan/extension-installer symfony/test-pack orm-fixtures liip/test-fixtures-bundle vincentlanglet/twig-cs-fixer friendsofphp/php-cs-fixer
````

### Détail du package :
````
composer require --dev phpstan/phpstan
composer require --dev phpstan/phpstan-symfony
composer require --dev phpstan/extension-installer
````
````
# test-pack embarque phpunit et quelques utilitaires pratiques 
composer require --dev symfony/test-pack
composer require --dev orm-fixtures

# Gère les transactions dans la DB et peut faire du cache
composer require --dev liip/test-fixtures-bundle
````
````
composer require --dev vincentlanglet/twig-cs-fixer
composer require --dev friendsofphp/php-cs-fixer

````

## 2. Configuration

- phpstan → parametrer au lvl 5 ou 6, cf phpstan.dist.neon
- php-cs-fixer → cf .php-cs-fixer.dist.php
- twig-cs-fixer → pas de config sur ce projet la config de base étant déjà très bien, cf doc si besoin
- phpunit → ajuster les variables dans .env.test

## 3. Utilisation

```
php bin/console doctrine:database:create --env=test --if-not-exists;
php bin/console doctrine:migrations:migrate --no-interaction --env=test

./vendor/bin/phpunit

```
> **Note :** Si vous rencontrez l'erreur `DatabaseRequired`, assurez-vous que la base de données de test est bien créée et accessible. Vous pouvez forcer le `DATABASE_URL` dans votre `phpunit.xml` ou `.env.test.local`.

## 4. Documentation

- phpunit → https://www.youtube.com/watch?v=VdKuEclVXG0
- fixtures → https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html
- DAMA + Liip fixtures → https://github.com/liip/LiipTestFixturesBundle/blob/master/doc/database-setup.md#dama-doctrine-test-bundle

## 5. Exemple d'utilisation de DAMA + Liip

Pour charger les fixtures dans le `setUp()` d'un test tout en profitant de l'isolation de DAMA :

```php
use App\DataFixtures\AppFixtures;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MyTest extends KernelTestCase
{
    protected ?AbstractDatabaseTool $databaseTool = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();
        $this->databaseTool->loadFixtures([
            AppFixtures::class,
        ]);
    }
}
```