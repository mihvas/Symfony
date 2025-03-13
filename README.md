# Symfony

This project is a Symfony-based application containerized using Docker. It includes containers for PHP, Nginx, and PostgreSQL, ensuring a reproducible development environment for local development and deployment.

## 1. Install the necessary

- **Git** – for version control.
- **Docker & Docker Compose** – for containerization.
- **PHP & Composer** – (optional).
- **Symfony CLI** – (optional).


## 2. Clone the Repository

Clone the repository from GitHub to your local machine:

```bash
git clone https://github.com/Rmgs123/Symfony-Project.git
```

## 3. Environment Setup

Edit the `.env` file to include your database and app settings.


## 4. Install PHP Dependencies

Install the necessary PHP dependencies using Composer:

```bash
composer install
```

## 5. Build and Run Containers

This project uses Docker to create an isolated environment. The Docker configuration includes:

- **docker-compose.yml:** Defines services for PHP, Nginx, and PostgreSQL.
- **docker/php/Dockerfile-php:** Builds the PHP container with necessary extensions and Composer.
- **docker/nginx/default.conf:** Configures Nginx to serve the Symfony application.

**Build the Docker images with:**

```bash
docker-compose build
```

Then, start the containers in detached mode:

```bash
docker-compose up -d
```

## 6. Verify the Setup

- **Web Server:**  
  Open your browser and go to [http://localhost](http://localhost). You should see the Symfony welcome page or your application’s default route.
