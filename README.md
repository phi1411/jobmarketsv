# Job Marketplace Platform

This is a job marketplace platform that facilitates the connection between companies and developers. It allows companies to post job openings, search for potential candidates, and manage the hiring process. Developers can explore job opportunities, apply for positions, and track their applications.

## Features

### Company Features

- Register an account and create a company profile.
- Post job openings, including job title, description, requirements, and desired skills.
- Edit or delete job postings as needed.
- Manage applications received for each job.
- Communicate with applicants through the platform's messaging system.
- Track the status of job postings and applications.

### Developer Features

- Register an account and create a developer profile.
- Search for jobs based on various criteria, such as location, job title, or skills required.
- View detailed job descriptions and requirements.
- Apply for jobs directly through the platform.
- Track application status and receive notifications.
- Communicate with company representatives for further discussion.

### General Features

- User authentication and authorization for secure access.
- Search functionality with filters and sorting options.
- User-friendly interface for easy navigation and interaction.
- Notifications and alerts for important updates.
- Responsive design for seamless usage on different devices.

## Technologies Used

- Backend: PHP 8.2
- Framework: Laravel
- Database: MySQL
- Authentication: [INSERT AUTHENTICATION LIBRARY NAME]
- Other dependencies: [INSERT OTHER RELEVANT DEPENDENCIES]

## Getting Started

To run the job marketplace platform locally, follow these steps:

- Clone the repository: `git clone https://github.com/basemax/JobMarketplaceDDDPHP.git`
- Install dependencies: `composer install`
- Set up the database and update the database configuration.
- Run migrations: `php migrate.php`
- Seed the database with initial data if necessary: `php seeder.php`
- Start the development server: php artisan serve
- Access the platform in your browser at `http://localhost:8000`

## Contributing

We welcome contributions to enhance the job marketplace platform. If you'd like to contribute, please follow these steps:

- Fork the repository.
- Create a new branch for your feature: git checkout -b feature/your-feature
- Commit your changes: git commit -m "Add your feature"
- Push to the branch: git push origin feature/your-feature
- Open a pull request explaining your changes.
- Please ensure that your code follows the established coding guidelines and passes any existing tests. Also, consider writing new tests to cover your changes.

## Routes

### Companies:

- `GET /companies`: Retrieve a list of all companies
- `POST /companies`: Create a new company
- `GET /companies/{id}`: Retrieve a specific company by ID
- `PUT /companies/{id}`: Update a specific company by ID
- `DELETE /companies/{id}`: Delete a specific company by ID

### Jobs:

- `GET /jobs`: Retrieve a list of all jobs
- `POST /jobs`: Create a new job
- `GET /jobs/{id}`: Retrieve a specific job by ID
- `PUT /jobs/{id}`: Update a specific job by ID
- `DELETE /jobs/{id}`: Delete a specific job by ID

### Applications:

- `GET /jobs/{id}/applications`: Retrieve all applications for a specific job
- `POST /jobs/{id}/applications`: Create a new application for a specific job
- `GET /applications/{id}`: Retrieve a specific application by ID
- `PUT /applications/{id}`: Update a specific application by ID
- `DELETE /applications/{id}`: Delete a specific application by ID

### Developers:

- `GET /developers`: Retrieve a list of all developers
- `POST /developers`: Create a new developer
- `GET /developers/{id}`: Retrieve a specific developer by ID
- `PUT /developers/{id}`: Update a specific developer by ID
- `DELETE /developers/{id}`: Delete a specific developer by ID

### Authentication:

- `POST /register`: Register a new user (developer or company)
- `POST /login`: Authenticate a user and obtain an access token
- `POST /logout`: Logout the user and invalidate the access token

### Profile:

- `GET /profile`: Retrieve the user's profile information
- `PUT /profile`: Update the user's profile information
- `PUT /profile/password`: Update the user's password

### Skills:

- `GET /skills`: Retrieve a list of all available skills
- `POST /skills`: Create a new skill
- `GET /skills/{id}`: Retrieve a specific skill by ID
- `PUT /skills/{id}`: Update a specific skill by ID
- `DELETE /skills/{id}`: Delete a specific skill by ID

### Favorites:

- `GET /favorites/jobs`: Retrieve a list of favorited jobs by the user
- `POST /favorites/jobs/{id}`: Add a job to the user's favorites
- `DELETE /favorites/jobs/{id}`: Remove a job from the user's favorites

### Saved Searches:

- `GET /saved-searches`: Retrieve a list of saved searches by the user
- `POST /saved-searches`: Save a search with specified criteria
- `DELETE /saved-searches/{id}`: Delete a saved search by ID

### Categories:

- `GET /categories`: Retrieve a list of all job categories
- `POST /categories`: Create a new job category
- `GET /categories/{id}`: Retrieve a specific job category by ID
- `PUT /categories/{id}`: Update a specific job category by ID
- `DELETE /categories/{id}`: Delete a specific job category by ID

### Locations:

- `GET /locations`: Retrieve a list of all available locations
- `POST /locations`: Create a new location
- `GET /locations/{id}`: Retrieve a specific location by ID
- `PUT /locations/{id}`: Update a specific location by ID
- `DELETE /locations/{id}`: Delete a specific location by ID

### Reviews:

- `GET /companies/{id}/reviews`: Retrieve all reviews for a specific company
- `POST /companies/{id}/reviews`: Create a new review for a specific company
- `GET /reviews/{id}`: Retrieve a specific review by ID
- `PUT /reviews/{id}`: Update a specific review by ID
- `DELETE /reviews/{id}`: Delete a specific review by ID

### Statistics:

- `GET /statistics/jobs`: Retrieve statistical data related to jobs (e.g., total jobs, top categories)
- `GET /statistics/companies`: Retrieve statistical data related to companies (e.g., total companies, top locations)

### Notifications:

- `GET /notifications`: Retrieve a list of notifications for the authenticated user
- `POST /notifications`: Create a new notification for the authenticated user
- `GET /notifications/{id}`: Retrieve a specific notification by ID
- `PUT /notifications/{id}`: Update a specific notification by ID
- `DELETE /notifications/{id}`: Delete a specific notification by ID

### Subscriptions:

- `POST /subscriptions/jobs`: Subscribe to receive notifications for new job postings based on specific criteria
- `DELETE /subscriptions/jobs/{id}`: Unsubscribe from notifications for a specific job subscription

### Reports:

- `GET /reports/jobs`: Generate a report of job postings based on various criteria (e.g., date range, category)
- `GET /reports/companies`: Generate a report of companies based on various criteria (e.g., location, industry)

### Admin:

- `GET /admin/jobs`: Retrieve a list of all job postings (admin access)
- `GET /admin/companies`: Retrieve a list of all companies (admin access)
- `GET /admin/users`: Retrieve a list of all users (admin access)
- `PUT /admin/users/{id}`: Update a user's profile or permissions (admin access)
- `DELETE /admin/users/{id}`: Delete a user's account (admin access)

## License

The job marketplace platform is open-source software licensed under the GPL-3.0 License.

## Contact

If you have any questions or suggestions regarding the job marketplace platform, please feel free to contact us.

Copyright 2023, Max Base
