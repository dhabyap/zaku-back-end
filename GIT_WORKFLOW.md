# 🔄 Git Workflow & Commit Standards

Complete guide untuk git workflow, commit message conventions, dan code review standards.

---

## 📋 Git Workflow Overview

```
TASK START
    ↓
Create Issue (ISSUE-XXX.md)
    ↓
Create Feature Branch (feature/task-name)
    ↓
Implement & Test (Update WORKING_ON.md)
    ↓
Document Implementation (IMPL-XXX.md)
    ↓
Create Commit (conventional commit format)
    ↓
Push & Create PR (PR-XXX.md)
    ↓
Code Review & Feedback
    ↓
Merge to Main Branch
    ↓
Update CHANGELOG.md
    ↓
Mark Task as DONE in TASK_LIST.md
```

---

## 🌿 Branch Naming Convention

### Format
```
<type>/<short-description>
```

### Types
- **feature/** - Fitur baru
- **fix/** - Bug fixes
- **refactor/** - Code refactoring
- **docs/** - Documentation updates
- **chore/** - Build, dependencies, etc
- **test/** - Test additions/modifications

### Examples
```
feature/jwt-authentication
feature/wallet-model-creation
fix/database-connection-timeout
refactor/user-model-relationships
docs/api-documentation
chore/composer-dependencies-update
test/auth-controller-tests
```

### Branch Naming Rules
- Use lowercase
- Separate words dengan hyphen (-)
- Max 50 characters
- Descriptive tapi singkat
- No special characters except hyphen

---

## 💬 Conventional Commit Format

### Complete Format

```
<type>(<scope>): <subject>
<BLANK LINE>
<body>
<BLANK LINE>
<footer>
```

### Type
Wajib ada. Tipe perubahan:
- **feat** - Fitur baru (MINOR version bump)
- **fix** - Bug fix (PATCH version bump)
- **refactor** - Refactoring code tanpa feature/fix
- **perf** - Performance improvement
- **test** - Test-related changes
- **docs** - Documentation changes
- **chore** - Build, dependencies, config changes
- **ci** - CI/CD changes
- **style** - Formatting, linting (no logic change)

### Scope
Optional. Area kode yang dipengaruhi:
```
feat(auth): add login endpoint
fix(wallet): correct saldo calculation
refactor(database): simplify query structure
```

Contoh scopes:
- auth
- user
- wallet
- transaction
- database
- api
- config
- test

### Subject
Max 50 characters, bukan 50++!

Rules:
- Dimulai dengan huruf kecil
- Tidak ada titik di akhir
- Gunakan imperative mood ("add" bukan "added" atau "adds")
- Explain WHAT (bukan WHY atau HOW)

### Body (Optional tapi Recommended)
- Tidak wajib untuk simple commits
- Jelaskan WHY, bukan WHAT
- Wrap di 72 characters
- Separated by blank line dari subject
- Gunakan bullet points untuk multiple points

### Footer (Optional)
Gunakan untuk:
- Reference issues: `Closes ISSUE-001`
- Breaking changes: `BREAKING CHANGE: description`
- Co-authors: `Co-authored-by: name <email>`

---

## ✅ Commit Message Examples

### Good Examples

**Simple feature commit**
```
feat(auth): add register endpoint

- Create RegisterRequest form validation
- Hash password dengan bcrypt
- Create wallet for new user
- Send verification email

Closes ISSUE-001
```

**Bug fix**
```
fix(database): resolve connection timeout issue

Database connections were timing out after 5 minutes
of inactivity. Added connection keep-alive setting
to config/database.php.

Closes ISSUE-045
```

**Documentation**
```
docs(readme): add setup instructions for development
```

**Refactoring**
```
refactor(models): simplify wallet-user relationships

Extract common relationship logic into trait untuk
reusability across models.
```

**Multiple commits flow**
```
feat(wallet): add wallet balance transfer feature

- Add TransferRequest validation
- Implement transfer logic dalam Wallet model
- Create TransferTransaction enum
- Add wallet balance validation

Closes ISSUE-012
---
fix(wallet): prevent negative balance pada transfer

Add additional validation untuk prevent insufficient
balance scenarios.

Related: ISSUE-012
---
test(wallet): add transfer feature tests

Add comprehensive unit tests untuk transfer functionality.

Related: ISSUE-012
```

### Bad Examples ❌

```
❌ update files
❌ fix stuff
❌ Fixed bugs
❌ Updated authentication
❌ Code review changes
❌ as per the requirements
```

---

## 📝 Commit Template

Buat file `.gitmessage` di root project:

```
# <type>(<scope>): <subject line (max 50 chars)>
#
# <body (explain why, wrap at 72 chars)>
#
# <footer>
# Reference Issues Like This:
# Closes ISSUE-001, ISSUE-002
# Resolves ISSUE-003
#
# Breaking Changes:
# BREAKING CHANGE: description
#
# Co-authored-by: name <email>
#
# Allowed types: feat, fix, refactor, perf, test, docs, chore, ci, style
# Allowed scopes: auth, user, wallet, transaction, database, api, config, test
```

Setup di git config:
```bash
git config commit.template .gitmessage
```

---

## 🔄 PR Description Format

### Template
```markdown
## Summary
[Brief description of changes]

## Type of Change
- [ ] New feature
- [ ] Bug fix
- [ ] Breaking change
- [ ] Documentation update

## Changes Made
- [Change 1]
- [Change 2]

## Related Issues
Closes ISSUE-XXX

## Testing
- [ ] Unit tests added
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style
- [ ] Self-reviewed code
- [ ] Added comments for complex logic
- [ ] Updated documentation
```

---

## ✨ Commit Best Practices

### DO ✅

1. **Commit Early & Often**
   - Logical chunks, not the whole feature
   - Easier to review dan understand
   - Easier to revert if needed

2. **Write Clear Messages**
   - Reviewers understand instantly
   - Future maintainers appreciate it
   - Git history becomes valuable

3. **One Concern Per Commit**
   - Feature A di satu commit
   - Bug fix di commit lain
   - Refactoring di commit terpisah

4. **Test Before Committing**
   - No broken code
   - All tests passing
   - No unrelated changes

5. **Reference Issues**
   - `Closes ISSUE-001`
   - Link code ke requirements
   - Automatic closing on merge

6. **Use Meaningful Scope**
   - `feat(auth)` vs `feat(misc)`
   - Clear area of change
   - Helps filtering git log

### DON'T ❌

1. **Don't Commit Large Unrelated Changes**
   - Feature + refactoring dalam 1 commit
   - Bug fix + cleanup dalam 1 commit

2. **Don't Write Vague Messages**
   - ❌ "update code"
   - ✅ "refactor user model to reduce duplication"

3. **Don't Mix Concerns**
   - Multiple features dalam 1 commit
   - Linting + feature dalam 1 commit

4. **Don't Modify History (main branch)**
   - No force push ke main
   - Rebase locally sebelum push
   - Keep main branch clean

5. **Don't Commit Debug Code**
   - No console.log() remnants
   - No commented-out code
   - No TODO comments without issue

---

## 🔀 Branch Management

### Creating Feature Branch
```bash
# Update main branch
git checkout main
git pull origin main

# Create feature branch
git checkout -b feature/task-description

# Verify
git branch
# * feature/task-description
#   main
```

### Before Pushing
```bash
# Rebase against main untuk clean history
git fetch origin
git rebase origin/main

# Fix any conflicts

# Push to remote
git push origin feature/task-description
```

### Pull Request Flow
```bash
# After push, create PR on GitHub/GitLab
# Add PR description (use PR-XXX.md template)
# Request reviewers
# Address feedback
# Merge when approved

# After merge, cleanup
git checkout main
git pull origin main
git branch -d feature/task-description
git push origin --delete feature/task-description
```

---

## 📊 Commit History Example

```bash
commit 7a3f2e1 (HEAD -> main)
Author: Developer <dev@example.com>
Date:   May 5, 2025 10:30 AM

    docs(changelog): update release notes for v0.1.0

commit 5b2c1a9
Author: Developer <dev@example.com>
Date:   May 5, 2025 10:00 AM

    fix(database): resolve connection timeout after 5min

    Database would timeout on idle connections.
    Added keep-alive configuration to prevent premature
    connection termination.
    
    Closes ISSUE-045

commit 4d9e8c3
Author: Developer <dev@example.com>
Date:   May 5, 2025 09:30 AM

    feat(auth): add jwt authentication system

    - Install tymon/jwt-auth package
    - Configure JWT guard in auth config
    - Create AuthController dengan login/register
    - Add JWT middleware untuk protected routes
    
    Closes ISSUE-002

commit 3e8f7c2
Author: Developer <dev@example.com>
Date:   May 5, 2025 08:00 AM

    feat(setup): install laravel project foundation

    Fresh Laravel 10.x installation dengan proper configuration.
    Database connection tested dan verified.
    
    Closes ISSUE-001
```

---

## 🔍 Git Command Cheat Sheet

### Status & Log
```bash
git status                    # Current status
git log --oneline            # Recent commits
git log -5                   # Last 5 commits
git log --graph --oneline    # Visual branch history
git diff                     # Uncommitted changes
git diff HEAD~1              # Changes dari commit sebelumnya
```

### Staging & Committing
```bash
git add .                    # Stage semua changes
git add file.php             # Stage specific file
git commit -m "message"      # Commit dengan message
git commit --amend           # Modify commit terakhir
```

### Branching
```bash
git branch                   # List branches
git checkout -b feature/abc  # Create dan switch branch
git branch -d branch-name    # Delete branch (local)
git push origin branch-name  # Push branch
```

### Rebasing & Merging
```bash
git rebase main              # Rebase current branch on main
git merge branch-name        # Merge branch-name ke current
git merge --squash branch    # Squash commits sebelum merge
```

### Viewing History
```bash
git show commit-hash         # Show commit details
git blame file.php           # See who changed each line
git log --author=name        # Commits by author
git log --since="2 weeks ago" # Recent commits
```

---

## 📚 References

- [Conventional Commits](https://www.conventionalcommits.org/)
- [Keep a Changelog](https://keepachangelog.com/)
- [Git Documentation](https://git-scm.com/doc)
- [GitHub Flow](https://guides.github.com/introduction/flow/)

---

## 🤝 Code Review Guidelines

### For Authors (Saat Membuat PR)
- [ ] Self-review sebelum submit
- [ ] Add detailed PR description
- [ ] Reference related issues
- [ ] Ensure all tests pass
- [ ] Add implementation notes

### For Reviewers
- [ ] Check code quality
- [ ] Verify tests coverage
- [ ] Look for security issues
- [ ] Ensure documentation complete
- [ ] Approve or request changes

### Review Comments Format
- **Question**: "What's the purpose of...?"
- **Suggestion**: "Consider using... instead"
- **Concern**: "This could cause... because..."
- **Praise**: "Great approach for..."

---

**Last Updated:** May 5, 2025  
**Version:** 1.0
