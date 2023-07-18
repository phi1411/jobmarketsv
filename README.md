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
- Database: MySQL

## Getting Started

To run the job marketplace platform locally, follow these steps:

- Clone the repository: `git clone https://github.com/basemax/JobMarketplaceDDDPHP.git`
- Install dependencies: `composer update`
- Set up the database and update the database configuration.
- Run migrations: `php migrate.php`
- Start project: `php -S localhsot:8000 public/index.php`
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

## Database and Tables

```sql
-- Table: users
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('company', 'developer') NOT NULL,
  token VARCHAR(255) DEFAULT NULL,
  token_expires_at TIMESTAMP DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: companies
CREATE TABLE companies (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  location VARCHAR(255),
  website VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: jobs
CREATE TABLE jobs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  company_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  requirements TEXT,
  location VARCHAR(255),
  category VARCHAR(255),
  type ENUM('full-time', 'part-time', 'contract', 'freelance') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Table: applications
CREATE TABLE applications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  job_id INT NOT NULL,
  developer_id INT NOT NULL,
  cover_letter TEXT,
  resume VARCHAR(255),
  status ENUM('pending', 'reviewed', 'accepted', 'rejected') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: skills
CREATE TABLE skills (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: job_skills
CREATE TABLE job_skills (
  id INT PRIMARY KEY AUTO_INCREMENT,
  job_id INT NOT NULL,
  skill_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- Table: favorites
CREATE TABLE favorites (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  job_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Table: saved_searches
CREATE TABLE saved_searches (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  criteria TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: categories
CREATE TABLE categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: locations
CREATE TABLE locations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: reviews
CREATE TABLE reviews (
  id INT PRIMARY KEY AUTO_INCREMENT,
  company_id INT NOT NULL,
  user_id INT NOT NULL,
  rating INT NOT NULL,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: notifications
CREATE TABLE notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  message TEXT,
  is_read BOOLEAN DEFAULT false,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: subscriptions
CREATE TABLE subscriptions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  criteria TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: payments
CREATE TABLE payments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  job_id INT NOT NULL,
  amount DECIMAL(10, 2) NOT NULL,
  status ENUM('pending', 'success', 'failed') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Table: reports
CREATE TABLE reports (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  report_type ENUM('jobs', 'companies') NOT NULL,
  criteria TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: admin_users
CREATE TABLE admin_users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: qualifications
CREATE TABLE qualifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  developer_id INT NOT NULL,
  degree VARCHAR(255),
  institution VARCHAR(255),
  major VARCHAR(255),
  start_date DATE,
  end_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: experiences
CREATE TABLE experiences (
  id INT PRIMARY KEY AUTO_INCREMENT,
  developer_id INT NOT NULL,
  company VARCHAR(255),
  position VARCHAR(255),
  start_date DATE,
  end_date DATE,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: conversations
CREATE TABLE conversations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  sender_id INT NOT NULL,
  recipient_id INT NOT NULL,
  subject VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: messages
CREATE TABLE messages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  conversation_id INT NOT NULL,
  sender_id INT NOT NULL,
  content TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: categories_jobs
CREATE TABLE categories_jobs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  job_id INT NOT NULL,
  category_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Table: reviews_developers
CREATE TABLE reviews_developers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  developer_id INT NOT NULL,
  user_id INT NOT NULL,
  rating INT NOT NULL,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Examples

Registration:
```curl
curl --location 'http://localhost:2323/register' \
--header 'Content-Type: application/json' \
--form 'name="AliAhmadi"' \
--form 'email="AliAhmadi@gmail.com"' \
--form 'password="1234"' \
--form 'role="developer"'
```
Response:
```json
{
    "status": "user registration successful"
}
```

if url does not exist:
```json
{
    "status": "Not Found"
}
```

if method not allowed:
```json
{
    "message": "method not allowed",
    "allowed methods": [
        "POST"
    ]
}
```
## License

The job marketplace platform is open-source software licensed under the GPL-3.0 License.

## Contact

If you have any questions or suggestions regarding the job marketplace platform, please feel free to contact us.

Copyright 2023, Max Base
