# 📚 Documentation Guide

Complete guide untuk understanding the documentation system di DOMPET Backend project.

---

## 🎯 Purpose

Dokumentasi system ini untuk:
1. **Track Progress** - Tahu siapa kerja apa dan sudah sampai mana
2. **Knowledge Sharing** - Setiap keputusan dan perubahan terdokumentasi
3. **Code Review** - Review process terstruktur dan transparan
4. **Version Control** - History semua changes untuk future reference
5. **Onboarding** - Junior developer bisa understand context dengan cepat

---

## 📁 Documentation Structure

```
/backend
├── README.md                           # Project overview (START HERE)
├── PRD-Backend.md                      # Product requirement document
├── TASK_LIST.md                        # All tasks untuk project
├── DOCUMENTATION_SYSTEM.md             # Panduan ini
├── CHANGELOG.md                        # History dari semua changes
├── GIT_WORKFLOW.md                     # Git dan commit standards
│
└── /docs
    ├── WORKING_ON.md                   # Current task progress (real-time)
    │
    ├── /issues                         # Task definitions
    │   ├── ISSUE-001.md                # Task 1: Setup Laravel
    │   ├── ISSUE-002.md                # Task 2: JWT Auth
    │   └── ...                         # More issues
    │
    ├── /implementation                 # Implementation details
    │   ├── IMPL-001.md                 # Task 1 implementation
    │   ├── IMPL-002.md                 # Task 2 implementation
    │   └── ...                         # More implementations
    │
    └── /pull-requests                  # Code review documents
        ├── PR-001.md                   # Task 1 PR review
        ├── PR-002.md                   # Task 2 PR review
        └── ...                         # More PRs
```

---

## 📖 How to Use Each File

### 1. PRD-Backend.md (Product Requirement Document)
**What:** Complete project specification and requirements  
**When:** Reference when unsure about functionality  
**Who:** Everyone (especially at project start)  
**Example:** "Apa itu field 'nomor_telepon' di user table?" → Check PRD-Backend.md

### 2. TASK_LIST.md (Master Task List)
**What:** All 27 development tasks organized by phase  
**When:** Pick next task to work on  
**Who:** Project manager, developers  
**How to Use:**
```
1. Read TASK_LIST.md
2. Find uncompleted task
3. Create ISSUE-XXX.md untuk task tersebut
4. Start working
```

### 3. DOCUMENTATION_SYSTEM.md (This File)
**What:** Explanation of documentation system  
**When:** First time setup atau when confused  
**Who:** Team leads, junior developers  
**Purpose:** Understand WHY documentation system exists

### 4. CHANGELOG.md (Release Notes)
**What:** Summary dari semua changes per version  
**When:** Want to know project history  
**Who:** Product team, QA, stakeholders  
**Format:**
```
## [Version] - Date
### Added/Fixed/Changed
- ✨ Feature added
- 🐛 Bug fixed
```

### 5. GIT_WORKFLOW.md (Git Standards)
**What:** Commit message format, branch naming, PR guidelines  
**When:** Before making commit atau creating PR  
**Who:** All developers  
**Key Info:**
- Conventional Commits format
- Branch naming rules
- PR description template

### 6. WORKING_ON.md (Real-Time Progress)
**What:** Current task yang sedang dikerjakan developer  
**When:** Update setiap jam (atau setiap progress)  
**Who:** Developer yang sedang kerja  
**Example:**
```
Current Task: ISSUE-005 - Create Transaction Model
Progress: 60% (model created, relationships done, testing in progress)
Blockers: None
Next: Add transaction helper methods
```

---

## 📋 Documentation Workflow

### For Each Task (27 tasks)

```
START TASK
    ↓
1. Read TASK_LIST.md → Pilih task
    ↓
2. Create ISSUE-XXX.md → Define apa yang harus dikerjakan
    ↓
3. Update WORKING_ON.md → Mark as in-progress, track detail
    ↓
4. DEVELOPMENT TIME
    ↓
5. Create IMPL-XXX.md → Document apa yang di-implement
    ↓
6. Create PR-XXX.md → Prepare untuk code review
    ↓
7. Code Review & Feedback
    ↓
8. Update CHANGELOG.md → Record completion
    ↓
9. Mark TASK_LIST.md as DONE ✅
    ↓
TASK COMPLETE
```

---

## 🚀 Quick Start for Junior Developer

### Day 1 - Setup

1. **Clone Project**
   ```bash
   git clone <repo-url>
   cd dompet-backend
   ```

2. **Read Documentation** (in order)
   - [ ] README.md - Project overview
   - [ ] PRD-Backend.md - Requirements
   - [ ] TASK_LIST.md - All tasks
   - [ ] DOCUMENTATION_SYSTEM.md - This guide
   - [ ] GIT_WORKFLOW.md - How to commit

3. **Understand Folder Structure**
   ```
   /backend
   ├── docs/ → All documentation
   ├── app/ → PHP code (will be created)
   ├── routes/ → API routes (will be created)
   └── ...
   ```

### Day 2 - First Task

1. **Pick First Task**
   - Open TASK_LIST.md
   - Find "Task 1.1 - Setup Laravel Project"
   - Click link → ISSUE-001.md

2. **Read The Issue**
   - Understand what need to be done
   - Read acceptance criteria
   - Note any dependencies

3. **Start Working**
   - Update WORKING_ON.md dengan progress
   - Follow implementation steps
   - Test every step

4. **Document Your Work**
   - Update WORKING_ON.md regularly
   - Create IMPL-001.md setelah selesai
   - Record issues yang encountered

5. **Prepare for Review**
   - Create PR-001.md
   - List all changes
   - Document testing done

### Day 3 - Submit

1. **Commit Code**
   ```bash
   git add .
   git commit -m "feat(setup): install laravel project"
   git push origin feature/setup-laravel
   ```

2. **Create Pull Request**
   - Use PR-001.md template
   - Reference ISSUE-001
   - Add implementation notes link

3. **Update Records**
   - Update CHANGELOG.md
   - Mark TASK_LIST.md as DONE
   - Notify team about completion

---

## 📊 File Usage Matrix

| File | Developer | QA | Manager | When |
|------|-----------|----|---------|----|
| PRD-Backend.md | ⭐⭐⭐ | ⭐⭐ | ⭐ | Always reference |
| TASK_LIST.md | ⭐⭐⭐ | ⭐ | ⭐⭐⭐ | Pick tasks |
| ISSUE-XXX.md | ⭐⭐⭐ | ⭐ | ⭐ | Before task |
| IMPL-XXX.md | ⭐⭐⭐ | ⭐⭐ | ⭐ | After coding |
| PR-XXX.md | ⭐⭐⭐ | ⭐⭐ | ⭐ | Code review |
| CHANGELOG.md | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ | Release notes |
| WORKING_ON.md | ⭐⭐⭐ | ⭐ | ⭐⭐ | Real-time |

⭐ = importance level

---

## 💡 Best Practices

### DO ✅

1. **Update WORKING_ON.md Real-Time**
   - Update setiap progress
   - Manager bisa track siapa kerja apa
   - Help planning next tasks

2. **Create Issue Before Coding**
   - Define requirements clearly
   - Prevent misunderstanding
   - Clear scope dari task

3. **Document As You Go**
   - Jangan tunggu task selesai
   - Create IMPL-XXX.md during development
   - Update WORKING_ON.md setiap jam

4. **Reference Issues in Commits**
   ```bash
   git commit -m "feat(auth): add login endpoint

   Closes ISSUE-001
   "
   ```

5. **Keep CHANGELOG Updated**
   - Update setelah setiap PR merge
   - Format yang consistent
   - Useful untuk release notes

### DON'T ❌

1. **Don't Work Without Issue Definition**
   - Always create ISSUE-XXX.md first
   - Clarify requirements dengan team
   - Prevent wasted effort

2. **Don't Merge Without Documentation**
   - PR-XXX.md must be complete
   - IMPL-XXX.md harus ada
   - CHANGELOG updated

3. **Don't Mix Multiple Tasks**
   - One task = one branch
   - One task = one ISSUE-XXX.md
   - One task = one PR

4. **Don't Forget CHANGELOG**
   - Easy to forget
   - Important untuk project history
   - Update setelah merge

---

## 🔍 Searching Documentation

### Find Task by Name
```
Open TASK_LIST.md → Search (Ctrl+F) → "name"
Example: Ctrl+F "Setup Laravel"
```

### Find Implementation Details
```
Go to /docs/implementation → Open IMPL-XXX.md
Atau search in DOCUMENTATION_SYSTEM.md
```

### Find Recent Changes
```
Open CHANGELOG.md → Check [Unreleased] section
```

### Find Git History
```
Run: git log --oneline
Atau: git log --graph --oneline
```

---

## 📝 Common Questions

**Q: Where do I find task details?**  
A: Open TASK_LIST.md, click task link → ISSUE-XXX.md

**Q: How do I track my progress?**  
A: Update WORKING_ON.md real-time, every progress

**Q: What commit message format?**  
A: Read GIT_WORKFLOW.md, use "feat(scope): message" format

**Q: Who reviews my code?**  
A: Team lead atau senior developer, via PR-XXX.md

**Q: How long should task take?**  
A: Check ISSUE-XXX.md → "Effort Estimate" section

**Q: What if I encounter issue?**  
A: Document dalam IMPL-XXX.md → "Issues Encountered" section

**Q: Can I work on multiple tasks?**  
A: Recommended 1 task at a time, focus pada completion

---

## 🎯 Success Criteria

Task is considered DONE when:
- ✅ ISSUE-XXX.md created dengan clear definition
- ✅ IMPL-XXX.md created dengan implementation details
- ✅ Code tested dan working
- ✅ PR-XXX.md created untuk code review
- ✅ Code reviewed dan approved
- ✅ CHANGELOG.md updated
- ✅ TASK_LIST.md marked as DONE
- ✅ WORKING_ON.md updated dengan completion

---

## 📞 Support

### Questions?
- Ask team lead atau senior developer
- Check related documentation files
- Review similar completed tasks

### Problems?
- Document dalam IMPL-XXX.md "Issues Encountered"
- Create follow-up ISSUE-XXX untuk bug
- Update CHANGELOG dengan problem noted

---

## 🔄 Documentation Maintenance

### Weekly
- Review CHANGELOG updates
- Check WORKING_ON.md progress
- Verify all links valid

### Monthly
- Archive completed ISSUE-XXX.md files
- Review documentation relevance
- Update examples if needed

### Before Release
- Ensure CHANGELOG complete
- All TASK_LIST.md marked DONE
- PR documentation reviewed

---

**Last Updated:** May 5, 2025  
**Version:** 1.0  
**Maintained By:** Development Team

---

## 🚀 Next Steps

1. ✅ Read this documentation
2. ✅ Familiarize dengan folder structure
3. ✅ Read GIT_WORKFLOW.md untuk commit standards
4. ✅ Open TASK_LIST.md
5. ✅ Pick first task dan start working!

Good luck! 🎉
