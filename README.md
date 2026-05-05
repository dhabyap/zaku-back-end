# 🏦 DOMPET Backend API

Professional Laravel REST API untuk Digital Wallet Management System.

---

## 📋 Quick Overview

| Item | Details |
|------|---------|
| **Project** | DOMPET Backend API |
| **Framework** | Laravel 10.x |
| **Language** | PHP 8.1+ |
| **Database** | MySQL 8.0+ / PostgreSQL 13+ |
| **Authentication** | JWT (JSON Web Tokens) |
| **Status** | 🚀 In Development |

---

## 🎯 Project Goals

✅ Build professional-grade REST API  
✅ Implement JWT authentication system  
✅ Manage digital wallet operations  
✅ Track financial transactions  
✅ Ensure code quality dan security  
✅ Documentation-first approach  

---

## 📚 Documentation (START HERE)

### Essential Docs (Read in Order)

1. **[PRD-Backend.md](./PRD-Backend.md)** - Product requirements & specifications
2. **[TASK_LIST.md](./TASK_LIST.md)** - All 27 tasks linked to GitHub issues
3. **[GIT_WORKFLOW.md](./GIT_WORKFLOW.md)** - Git standards & commit conventions
4. **[CHANGELOG.md](./CHANGELOG.md)** - Project history & changes

### GitHub Issues (Task Tracking)

- **[All Issues](https://github.com/YOUR_ORG/YOUR_REPO/issues)** - Task definitions & progress
- **[Pull Requests](https://github.com/YOUR_ORG/YOUR_REPO/pulls)** - Code reviews
- **[Issue Templates](https://github.com/YOUR_ORG/YOUR_REPO/issues/new/choose)** - Use templates untuk create issues

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+ installed
- Composer installed
- MySQL 8.0+ installed
- Node.js (optional, for build tools)

### Setup Local Development

```bash
# 1. Clone repository
git clone <repository-url>
cd dompet-backend

# 2. Install dependencies
composer install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dompet_db
DB_USERNAME=root
DB_PASSWORD=your_password

# 5. Create database
mysql -u root -p
> CREATE DATABASE dompet_db;
> exit;

# 6. Run migrations
php artisan migrate

# 7. Start development server
php artisan serve
# Server running on http://127.0.0.1:8000
```

### Verify Installation
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPDO();  # Should return PDO object

# Test server
# Open browser: http://127.0.0.1:8000
```

---

## 📁 Project Structure

```
dompet-backend/
├── app/                           # Application code
│   ├── Http/
│   │   ├── Controllers/           # API controllers
│   │   ├── Middleware/            # Custom middleware
│   │   ├── Requests/              # Form validation
│   │   └── Resources/             # API resources
│   ├── Models/                    # Eloquent models
│   ├── Traits/                    # Reusable traits
│   └── Exceptions/                # Custom exceptions
│
├── routes/
│   ├── api.php                    # API routes
│   └── web.php                    # Web routes
│
├── database/
│   ├── migrations/                # Database migrations
│   └── seeders/                   # Database seeders
│
├── config/                        # Application configuration
├── storage/                       # Logs, uploads, cache
├── public/                        # Web root
├── tests/                         # Test files
├── resources/                     # Views, assets
│
├── docs/                          # Documentation
│   ├── issues/                    # Task definitions
│   ├── implementation/            # Implementation notes
│   └── pull-requests/             # PR documentation
│
├── DOCUMENTATION_SYSTEM.md        # Doc system guide
├── TASK_LIST.md                   # All tasks (27 items)
├── PRD-Backend.md                 # Product requirements
├── CHANGELOG.md                   # Version history
├── GIT_WORKFLOW.md                # Git standards
├── .env.example                   # Environment template
├── composer.json                  # PHP dependencies
└── artisan                        # Laravel CLI
```

---

## 🛠️ Development Workflow

### For Each Task

```
1️⃣  GITHUB ISSUE CREATED
    ├─ Task definition
    ├─ Acceptance criteria
    └─ Labels & assignee

2️⃣  CREATE FEATURE BRANCH
    └─ git checkout -b feature/task-name

3️⃣  DEVELOPMENT
    ├─ Code implementation
    ├─ Add comments dalam issue
    └─ Test thoroughly

4️⃣  COMMIT & PUSH
    ├─ git commit -m "feat(scope): message"
    ├─ git push origin feature/task-name
    └─ Closes #ISSUE_NUMBER

5️⃣  PULL REQUEST
    ├─ Reference GitHub issue
    ├─ Use PR template
    └─ Code review

6️⃣  MERGE
    ├─ GitHub auto-closes issue
    └─ Update CHANGELOG.md

7️⃣  DONE ✅
```

### Quick Workflow

1. Open [GitHub Issues](https://github.com/YOUR_ORG/YOUR_REPO/issues)
2. Pick task yang belum done
3. Read issue details & acceptance criteria
4. Create feature branch
5. Code & test
6. Create PR (reference issue)
7. Merge & done!

---

## 📊 Development Phases

### Phase 1: Project Setup & Infrastructure ⏳
- Task 1.1: Setup Laravel Project
- Task 1.2: Install JWT Authentication
- Task 1.3: Configure Database & Environment

### Phase 2: Database Design & Migrations ⏳
- Task 2.1: Create User Migration
- Task 2.2: Create Wallet Migration
- Task 2.3: Create Transaction Migration
- Task 2.4: Create Verification Code Migration

### Phase 3: Models & Relationships ⏳
- Task 3.1: Create User Model
- Task 3.2: Create Wallet Model
- Task 3.3: Create Transaction Model
- Task 3.4: Create VerificationCode Model

### Phase 4: Request Validation & Resources ⏳
- Task 4.1: Create Form Request Classes
- Task 4.2: Create API Resource Classes

### Phase 5: Authentication Endpoints ⏳
- Task 5.1: POST /api/auth/register
- Task 5.2: POST /api/auth/login
- Task 5.3: POST /api/auth/verify-email
- Task 5.4: POST /api/auth/resend-verification
- Task 5.5: POST /api/auth/change-password
- Task 5.6: POST /api/auth/forgot-password

### Phase 6: Middleware & Utilities ⏳
- Task 6.1: Create JWT Authentication Middleware
- Task 6.2: Create ApiResponse Trait
- Task 6.3: Create Custom Exception Handler

### Phase 7: API Routes & Documentation ⏳
- Task 7.1: Setup API Routes
- Task 7.2: Setup Swagger Documentation

### Phase 8: Testing & QA ⏳
- Task 8.1: Test Authentication Endpoints
- Task 8.2: Test Security & Error Handling
- Task 8.3: Test Database Transactions

---

## 📋 Features

### Authentication System
- ✅ User registration dengan email
- ✅ User login dengan JWT tokens
- ✅ Email verification system
- ✅ Password change functionality
- ✅ Forgot password & reset
- ✅ Token refresh mechanism

### Wallet Management
- ✅ Create wallet untuk setiap user
- ✅ View wallet balance
- ✅ Top-up functionality
- ✅ Withdrawal functionality
- ✅ Transfer between wallets

### Transaction Tracking
- ✅ Record semua transactions
- ✅ Track transaction status
- ✅ Transaction history
- ✅ Transaction details

### Security
- ✅ JWT authentication
- ✅ Password hashing (bcrypt)
- ✅ CORS configuration
- ✅ Input validation
- ✅ SQL injection prevention
- ✅ Rate limiting ready

---

## 🧪 Testing

### Unit Tests
```bash
php artisan test
```

### Manual Testing (Postman/Insomnia)
```bash
# Test API endpoints
POST /api/auth/register
POST /api/auth/login
POST /api/auth/verify-email
GET /api/wallet (protected)
```

### Database Tests
```bash
php artisan tinker
>>> DB::connection()->getPDO();
```

---

## 📚 API Documentation

### Available Endpoints (Phase 1)
- [x] Authentication endpoints (6 total)

### Upcoming Endpoints
- [ ] Wallet endpoints (PHASE 5+)
- [ ] Transaction endpoints (PHASE 5+)
- [ ] User profile endpoints (PHASE 3+)

Full API documentation available at:
- Swagger UI: `http://localhost:8000/api/documentation` (when enabled)
- Postman Collection: `./docs/postman_collection.json` (to be created)

---

## 🔐 Security Considerations

### Implemented
- ✅ JWT token-based authentication
- ✅ Password hashing dengan bcrypt
- ✅ CORS headers configured
- ✅ Input validation on all endpoints
- ✅ SQL injection prevention (Eloquent ORM)

### To Implement
- [ ] Rate limiting
- [ ] API versioning
- [ ] Request logging
- [ ] Error handling improvements
- [ ] Security headers (HSTS, CSP, etc)

---

## 🐛 Debugging

### Enable Debug Mode
```bash
# In .env
APP_DEBUG=true  # Development only!
```

### View Logs
```bash
# Real-time logs
tail -f storage/logs/laravel.log

# Or in tinker
php artisan tinker
>>> Log::info('test');
```

### Database Debugging
```bash
# Enable query logging in .env
DB_QUERY_LOG=true

# View queries in tinker
php artisan tinker
>>> DB::enableQueryLog();
>>> DB::getQueryLog();
```

### Issues During Development?
- Check **[GitHub Issues](https://github.com/YOUR_ORG/YOUR_REPO/issues)** untuk existing problems
- Create new issue jika bug ditemukan
- Add comment dalam issue untuk update status

---

## 🚀 Performance

### Development Optimization
- [ ] Database indexing
- [ ] Query optimization
- [ ] Caching strategy
- [ ] API response optimization

### Production Checklist
- [ ] APP_DEBUG=false
- [ ] Cache configuration
- [ ] Database connection pooling
- [ ] Error handling & logging
- [ ] HTTPS/SSL enabled
- [ ] Rate limiting enabled
- [ ] Monitoring setup

---

## 🤝 Contributing

### GitHub Issues Workflow

1. **Issue Created** (by project manager/team lead)
   ```
   Title: [TASK] Task description
   Description: Full details & acceptance criteria
   Labels: phase-1, high-priority
   Assignee: Developer name
   ```

2. **Assign to Developer** → Developer starts working

3. **Create Feature Branch**
   ```bash
   git checkout -b feature/task-description
   ```

4. **Development & Commits**
   ```bash
   git commit -m "feat(scope): detailed message"
   ```

5. **Push & Create PR**
   ```bash
   git push origin feature/task-description
   # Create PR on GitHub
   ```

6. **PR Description** (use template in `.github/PULL_REQUEST_TEMPLATE/pull_request.md`)
   ```
   Closes #ISSUE_NUMBER
   - [ ] Code review checklist
   ```

7. **Code Review** → Feedback & improvements

8. **Merge** → GitHub auto-closes issue

### Code Standards
- Follow PSR-12 PHP standards
- Use meaningful variable names
- Add comments for complex logic
- Write clean, readable code
- Test before committing

### Commit Convention
```
feat(auth): add login endpoint
fix(database): resolve connection timeout
docs(readme): update setup instructions
refactor(models): simplify relationships
```

See [GIT_WORKFLOW.md](./GIT_WORKFLOW.md) for detailed guidelines.

---

## 📞 Support & Questions

### For Questions About:
- **Requirements** → Check PRD-Backend.md
- **Current Tasks** → Check TASK_LIST.md
- **How to Setup** → Check docs/DOCUMENTATION_GUIDE.md
- **Git Standards** → Check GIT_WORKFLOW.md
- **Implementation Details** → Check docs/implementation/

### Common Issues

**Q: Database connection fails?**
```
A: 1. Ensure MySQL is running
   2. Check .env database credentials
   3. Run: php artisan migrate
```

**Q: Migrations don't run?**
```
A: 1. Check database connection
   2. Run: php artisan migrate --step
   3. Check storage/logs/laravel.log for errors
```

**Q: Where do I find which task to work on?**
```
A: Open TASK_LIST.md
   Find task with status [  ] (empty checkbox)
   Click task link → Read ISSUE-XXX.md
   Start working!
```

---

## 📊 Project Status

### Overall Progress
- Total Tasks: 27
- Completed: 0 (0%)
- In Progress: 0
- Pending: 27 (100%)

### Current Phase
🚀 **Phase 1: Project Setup & Infrastructure**
- Task 1.1: Setup Laravel Project (Not Started)
- Task 1.2: Install JWT Authentication (Pending)
- Task 1.3: Configure Database (Pending)

---

## 📈 Roadmap

### Q2 2025
- [x] Project structure & documentation
- [ ] Authentication system (Phase 1-3)
- [ ] Database schema (Phase 2)
- [ ] API endpoints (Phase 5-7)

### Q3 2025
- [ ] Testing & QA (Phase 8)
- [ ] API documentation (Swagger)
- [ ] Performance optimization
- [ ] Security audit

### Q4 2025
- [ ] Production deployment
- [ ] Monitoring setup
- [ ] User acceptance testing
- [ ] Release v1.0.0

---

## 📄 License

[Your License Here]

---

## 👥 Team

- **Project Manager:** [Name]
- **Senior Developer:** [Name]
- **Junior Developers:** [Names]

---

## 🎉 Getting Started

### Next Steps:
1. ✅ Read [PRD-Backend.md](./PRD-Backend.md)
2. ✅ Review [TASK_LIST.md](./TASK_LIST.md)
3. ✅ Read [GIT_WORKFLOW.md](./GIT_WORKFLOW.md)
4. ✅ Setup local development environment
5. ✅ Go to **[GitHub Issues](https://github.com/YOUR_ORG/YOUR_REPO/issues)** & pick first task
6. ✅ Read issue details & start coding!

---

**Last Updated:** May 5, 2026  
**Status:** 🚀 In Development  
**Version:** 0.1.0 (Initial Setup)
**Tracking:** [GitHub Issues](https://github.com/YOUR_ORG/YOUR_REPO/issues)

Let's build something great! 💪
