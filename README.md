# HUSUEMS - Haramaya University Student Union Election Management System

![PHP](https://img.shields.io/badge/PHP-7.4+-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange)
![License](https://img.shields.io/badge/License-MIT-green)

A comprehensive web-based election management system designed for Haramaya University Student Union elections. Features secure voting, real-time results, and complete administrative control.

## 🌟 Features

### Student Features
- **Secure Authentication** - Student ID and name verification
- **Per-Position Voting** - Vote for President, Vice President, and Secretary separately
- **Flexible Voting** - Vote for positions at different times
- **Vote Tracking** - Unique tracking codes for each vote
- **Duplicate Prevention** - Cannot vote twice for the same position
- **Responsive Design** - Works on desktop and mobile devices

### Admin Features
- **Election Management** - Create, open, close, and delete elections
- **Voter Management** - Add, edit, and remove registered voters
- **Candidate Management** - Add candidates with photos and details
- **Results Dashboard** - Real-time vote counting and winner determination
- **Position-Based Results** - View results per position with percentages

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx) or PHP built-in server
- Modern web browser

## 🚀 Installation

### Local Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/HUSUEMS.git
   cd HUSUEMS
   ```

2. **Create database**
   ```bash
   mysql -u root -p
   CREATE DATABASE election_system;
   EXIT;
   ```

3. **Import database schema**
   ```bash
   mysql -u root -p election_system < database.sql
   ```

4. **Apply migration for per-position voting**
   ```bash
   mysql -u root -p election_system
   ```
   ```sql
   ALTER TABLE votes ADD UNIQUE KEY unique_vote_per_position (election_id, student_id, position);
   ALTER TABLE votes DROP INDEX unique_vote;
   EXIT;
   ```

5. **Configure database connection**
   
   Edit `config/db.php`:
   ```php
   $host = 'localhost';
   $db   = 'election_system';
   $user = 'root';
   $pass = 'your_password';
   ```

6. **Start development server**
   ```bash
   php -S localhost:8000 -c php.ini
   ```

7. **Access the application**
   - Student Login: http://localhost:8000/index.php
   - Admin Panel: http://localhost:8000/admin/

## 🌐 Online Deployment

### Free Hosting (InfinityFree)

1. Sign up at [InfinityFree](https://www.infinityfree.net/)
2. Upload all files to `htdocs/`
3. Create MySQL database via control panel
4. Import `database.sql` via phpMyAdmin
5. Run migration SQL for per-position voting
6. Update `config/db.php` with hosting credentials

**Detailed guide:** See `infinityfree_deployment.md`

### Paid Hosting (Hostinger/cPanel)

1. Purchase hosting plan
2. Upload files via FTP or File Manager
3. Create database in cPanel
4. Import schema and run migration
5. Update configuration
6. Enable SSL certificate

**Detailed guide:** See `deployment_guide.md`

## 📁 Project Structure

```
HUSUEMS/
├── admin/                  # Admin dashboard pages
│   ├── index.php          # Admin dashboard
│   ├── voters.php         # Voter management
│   ├── candidates.php     # Candidate management
│   ├── elections.php      # Election management
│   └── login.php          # Admin login
├── assets/                # Static resources
│   └── style.css          # Main stylesheet
├── config/                # Configuration files
│   └── db.php             # Database connection
├── includes/              # Shared functions
│   └── functions.php      # Core utilities
├── index.php              # Student login page
├── vote.php               # Voting interface
├── database.sql           # Database schema
└── README.md              # This file
```

## 🔧 Configuration

### Database Configuration
Edit `config/db.php` to set your database credentials.

### Admin Account
Create admin account by visiting `/add_admin.php` (delete after use).

### Voter Registration
Add voters through admin panel or use bulk import.

## 💻 Usage

### For Students

1. Visit the login page
2. Enter Student ID and Full Name
3. Select candidates for each position
4. Click "Confirm My Vote for [Position]"
5. Receive confirmation and tracking code

### For Administrators

1. Login to admin panel
2. Create new election
3. Add candidates with positions
4. Add registered voters
5. Open election for voting
6. Monitor results in real-time
7. Close election when complete

## 🔒 Security Features

- **Input Validation** - All user inputs are sanitized
- **SQL Injection Prevention** - Prepared statements used throughout
- **Session Management** - Secure session handling
- **Duplicate Vote Prevention** - Database constraints and validation
- **Unique Tracking Codes** - Each vote gets a unique identifier
- **Position-Specific Constraints** - One vote per position per student

## 🗄️ Database Schema

### Tables

- **admin** - Administrator accounts
- **voters** - Registered student voters
- **elections** - Election records
- **candidates** - Candidate information with positions
- **votes** - Cast votes with tracking codes

### Key Constraint
```sql
UNIQUE KEY unique_vote_per_position (election_id, student_id, position)
```
Ensures one vote per position per student per election.

## 🎨 Customization

### Styling
Edit `assets/style.css` to customize:
- Colors and branding
- Layout and spacing
- Button styles
- Responsive breakpoints

### Positions
Modify positions in `vote.php` and admin pages to add/remove voting positions.

## 🐛 Troubleshooting

### Database Connection Failed
- Check credentials in `config/db.php`
- Verify MySQL service is running
- Confirm database exists

### Cannot Vote
- Verify election is active
- Check if already voted for that position
- Confirm voter is registered

### Admin Login Issues
- Ensure admin account exists
- Check password hash in database
- Clear browser cache and cookies

## 📊 Features Roadmap

- [x] Per-position voting
- [x] Horizontal candidate layout
- [x] Admin dashboard
- [x] Real-time results
- [ ] Email notifications
- [ ] Vote verification system
- [ ] Multi-language support
- [ ] Advanced analytics

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👥 Authors

- **Sultan** - Initial work and development

## 🙏 Acknowledgments

- Haramaya University Student Union
- PHP and MySQL communities
- All contributors and testers

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Contact: [your-email@example.com]

## 📚 Documentation

- **Folder Structure:** See `folder_structure.md`
- **Deployment Guide:** See `deployment_guide.md`
- **System Overview:** See `system_overview.md`
- **Online Deployment:** See `infinityfree_deployment.md`



**Made with ❤️ for Haramaya University Student Union**