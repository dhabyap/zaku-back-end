# 🐙 GitHub Issues Workflow

Complete guide untuk menggunakan GitHub Issues untuk task tracking dan project management.

---

## 📋 Overview

GitHub Issues adalah primary system untuk:
- ✅ Task definitions dan requirements
- ✅ Progress tracking
- ✅ Team communication
- ✅ Bug reporting
- ✅ Linking code ke requirements

---

## 🚀 Quick Start

### 1. View All Tasks
```
Go to: https://github.com/YOUR_ORG/YOUR_REPO/issues
```

### 2. Pick a Task
- Click issue dari list
- Read issue description & acceptance criteria
- Check if assigned to you

### 3. Start Working
```bash
git checkout -b feature/task-description
```

### 4. Add Progress Comments
```
Dalam issue GitHub:
[Add comment dengan progress update]

Example:
"Started working on this. Created User model with relationships. 
Testing database connections next."
```

### 5. Create Pull Request
```bash
git push origin feature/task-description
# Click "Compare & Pull Request" on GitHub
```

### 6. Reference Issue in PR
```
PR Description:
Closes #1
```

When merged, issue auto-closes ✅

---

## 🏷️ Issue Labels

### By Phase
- `phase-1` - Setup & Infrastructure
- `phase-2` - Database Design
- `phase-3` - Models & Relationships
- `phase-4` - Request Validation
- `phase-5` - Authentication
- `phase-6` - Middleware & Utilities
- `phase-7` - API Routes
- `phase-8` - Testing & QA

### By Type
- `feature` - New feature
- `fix` - Bug fix
- `refactor` - Code refactoring
- `docs` - Documentation
- `chore` - Build, dependencies

### By Priority
- `high-priority` - Urgent, blocking other tasks
- `medium-priority` - Normal priority
- `low-priority` - Nice to have

### By Status
- `pending` - Not started
- `in-progress` - Currently being worked on
- `blocked` - Waiting for something
- `review` - Waiting for code review
- `done` - Completed

---

## 📝 Creating an Issue

### Using Template

1. Go to: **Issues** → **New Issue** → **Choose template**
2. Select **Development Task**
3. Fill in the form:

```markdown
---
name: Development Task
about: Task untuk project development
title: "[TASK] Setup Laravel Project"
labels: phase-1, setup, high-priority
assignees: 'developer-username'
---

## Title
Setup Laravel Project

## Description
Fresh Laravel project installation dengan konfigurasi dasar.

## Problem
Backend belum ada, dibutuhkan base Laravel untuk development.

## Expected Result
- Laravel installed
- Database connected
- Environment configured

## Acceptance Criteria
- [ ] Laravel installed
- [ ] .env configured
- [ ] Database connected
- [ ] php artisan serve works

[Additional sections...]
```

### Quick Issue

Atau direct create dengan command (jika GitHub CLI installed):

```bash
gh issue create \
  --title "[TASK] Setup Laravel Project" \
  --body "Fresh Laravel project setup..." \
  --label phase-1 \
  --label setup
```

---

## 💬 Working with Issues

### Add Progress Comment

```
[Di dalam GitHub issue]

Click "Comment" tab

Type:
"Started working on database migrations.
- Created users table ✓
- Creating wallets table...
- Testing connections after"
```

### Mark as In Progress

```
In GitHub issue:
- Add label: in-progress
- Assign to yourself: Assignees → your-name
```

### Link to Code

```
In PR atau Commit message:
"Closes #1"
"Fixes #5"
"Related to #3"

GitHub automatically links & shows relationship
```

### Move to Code Review

```
After push:
- Create Pull Request referencing issue
- GitHub shows "## This PR will close issue #1 when merged"
```

---

## 🔄 Linking Issues to PRs

### Option 1: PR Description

```markdown
## Summary
Implemented user authentication system

Closes #1
Closes #2
Closes #3
```

GitHub auto-detects & links!

### Option 2: Commit Message

```bash
git commit -m "feat(auth): add login endpoint

Closes #1
"
```

### Option 3: Manual Link

In PR or Issue, mention:
- `#1` - Links to issue 1
- `Closes #1` - Links & closes
- `Fixes #1` - Links & closes
- `Related to #1` - Links (no auto-close)

---

## 📊 Viewing Project Progress

### Issues Dashboard
```
https://github.com/YOUR_ORG/YOUR_REPO/issues
```

### Filter by Status
```
Click "Filters" atau search:
is:open                    # Open issues
is:closed                  # Closed issues
label:phase-1              # Phase 1 tasks
assignee:@me               # Assigned to me
label:in-progress          # Currently working
```

### Sort Issues
```
Sort By:
- Newest
- Oldest
- Most commented
- Updated recently
```

---

## ✅ Completing a Task

### Step 1: Commit & Push
```bash
git add .
git commit -m "feat(auth): add login endpoint

- Implement AuthController
- Add JWT token generation
- Test authentication flow

Closes #15
"
git push origin feature/add-auth-login
```

### Step 2: Create PR
```
On GitHub:
1. Click "Compare & Pull Request"
2. Use PR template
3. Reference issue: "Closes #15"
4. Request reviewers
```

### Step 3: Code Review
```
Reviewer:
- Reviews code
- Suggests changes
- Approves when ready
```

### Step 4: Merge PR
```
After approval:
1. Click "Merge Pull Request"
2. GitHub auto-closes issue #15 ✅
3. Issue moved to "Closed"
4. Label updates to "done"
```

### Step 5: Verify Closure
```
In GitHub issue #15:
[Shows "merged" status]
[Shows PR that closed it]
```

---

## 🐛 Bug Reports

### Create Bug Issue

```markdown
## Title
[BUG] User registration fails with invalid email

## Description
User registration endpoint returns 500 error when 
email has special characters.

## Problem
Special characters in email not handled properly

## Steps to Reproduce
1. POST /api/auth/register
2. Use email: user+test@example.com
3. See 500 error

## Expected
Registration should succeed or return validation error

## Acceptance Criteria
- [ ] Special chars handled
- [ ] Proper validation error
- [ ] Test case added
```

---

## 🔗 Quick Reference

### Issue URLs
```
All Issues:
https://github.com/YOUR_ORG/YOUR_REPO/issues

Single Issue:
https://github.com/YOUR_ORG/YOUR_REPO/issues/1

Create New:
https://github.com/YOUR_ORG/YOUR_REPO/issues/new
```

### Useful Filters
```
Open Phase 1 tasks:
is:open label:phase-1

My assigned tasks:
assignee:@me is:open

In progress:
label:in-progress

Blocked tasks:
label:blocked
```

### Common Commands (GitHub CLI)
```bash
# List all issues
gh issue list

# Create issue
gh issue create --title "Task name" --body "Description"

# Close issue
gh issue close 15

# Add labels
gh issue edit 15 --add-label "in-progress"

# View issue
gh issue view 15
```

---

## 💡 Best Practices

### DO ✅

1. **Read issue completely** sebelum mulai coding
2. **Update issue dengan progress** - Add comments frequently
3. **Reference issue dalam commits** - `Closes #1`
4. **Link related issues** - `Related to #3`
5. **Use labels consistently** - Helps filtering
6. **Assign to yourself** - Shows you're working
7. **Set correct phase label** - For organization
8. **Add acceptance criteria** - Clear scope

### DON'T ❌

1. **Don't start without reading issue**
2. **Don't forget to reference in commit**
3. **Don't use wrong labels** - Confuses team
4. **Don't leave issue unassigned** - Shows status
5. **Don't merge without closing issue** - Use `Closes #X`
6. **Don't forget to update status labels**

---

## 📞 Getting Help

### Questions About Task?
- Check issue description
- Read acceptance criteria
- Add comment dengan pertanyaan
- Team will reply

### Blocked?
- Add label: `blocked`
- Add comment: "Blocked by #X because..."
- Tag team member: `@senior-dev`

### Bug Found?
- Create new issue
- Label: `bug`
- Reference related task: "Related to #X"

---

## 🎯 Example Workflow

### Day 1 - Morning
```
1. Go to GitHub Issues
2. Pick issue #1 "Setup Laravel Project"
3. Click "Assignees" → Assign to me
4. Add label "in-progress"
```

### Day 1 - During Work
```
1. Create branch: git checkout -b feature/setup-laravel
2. Code & test
3. Add comment in issue: "Created .env, connected database..."
```

### Day 2 - Complete
```
1. Commit: git commit -m "feat(setup): install laravel
            Closes #1"
2. Push: git push origin feature/setup-laravel
3. Create PR: Reference issue in description
4. After merge: Issue auto-closes ✅
```

### Day 2 - Verify
```
1. Go to GitHub issue #1
2. Shows "merged" status
3. Shows PR that closed it
4. Task complete!
```

---

## 📊 Example Issue

```
# Issue #1: Setup Laravel Project

## Title
Setup Laravel Project dengan konfigurasi dasar

## Description
Fresh Laravel project installation dengan 
MySQL database configuration dan environment setup.

## Problem
- Backend belum ada
- Base Laravel diperlukan untuk development
- Environment configuration belum setup

## Expected Result
- Laravel project siap untuk development
- Database terkoneksi dan working
- Environment properly configured

## Acceptance Criteria
- [x] Laravel installed
- [x] .env configured
- [x] Database connected
- [x] php artisan serve works
- [x] Migrations executed

## Labels
- phase-1
- setup
- high-priority

## Assigned
- developer-name

## Status
- [x] MERGED (PR #1)
- Auto-closed

## Linked PR
- #1: feat(setup): install laravel project
```

---

**Last Updated:** May 5, 2026  
**For:** Zaku Backend Project
**Repository:** https://github.com/YOUR_ORG/YOUR_REPO
