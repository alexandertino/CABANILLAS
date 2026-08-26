# Clínica Cabanillas

Sistema de gestión para una clínica odontológica desarrollado con **Laravel**, **PostgreSQL** y **Docker mediante Laravel Sail**. El proyecto busca centralizar la administración de pacientes, profesionales, consultorios, citas, servicios y pagos en una plataforma organizada y escalable.

Actualmente el proyecto se encuentra en su etapa inicial de desarrollo. La infraestructura base ya cuenta con Laravel, PostgreSQL ejecutándose mediante Docker, Laravel Sail para la gestión del entorno de desarrollo y pgAdmin para la administración visual de la base de datos.

## Tecnologías

* Laravel
* PHP
* PostgreSQL
* Docker
* Laravel Sail
* Composer
* Vite
* Vue 3 *(previsto para la interfaz frontend)*
* pnpm *(previsto para la gestión de dependencias frontend)*

## Módulos iniciales

El sistema contempla inicialmente la gestión de:

* Pacientes
* Profesionales odontológicos
* Consultorios
* Citas
* Estados de citas
* Servicios odontológicos
* Historial de estados de citas
* Métodos de pago
* Pagos

## Entorno de desarrollo

El proyecto utiliza Docker para mantener un entorno de desarrollo consistente entre diferentes computadoras y facilitar el trabajo colaborativo.

La configuración privada del proyecto se almacena en el archivo `.env`, por lo que este archivo **no debe ser incluido en el repositorio**.
