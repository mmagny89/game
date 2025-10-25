## Prérequis
- Docker

## Setup :
```bash
docker compose up -d --build
docker compose exec php php bin/console doctrine:migrations:migrate -n
```

## Accès
> http://localhost:8080 (selon ta stack).

## Licence
> MIT