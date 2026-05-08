# Renovate Auto-Merge Configuration

## Overview

This repository uses Renovate Bot with auto-merge functionality enabled. The `platformAutomerge` is set to `true`, which enables GitHub's native auto-merge feature for Renovate pull requests.

## Auto-Merge Requirements

**IMPORTANT:** Before any auto-merge occurs, all required checks must pass, including:

### ✅ Required Tests

- **Psalm Static Analysis** - Must pass successfully
- All other CI/CD pipeline tests must pass
- Branch protection rules must be satisfied

## How It Works

1. Renovate creates a pull request for a dependency update
2. GitHub's auto-merge is automatically enabled on the PR
3. GitHub Actions/CI pipeline runs automatically
4. **Psalm static analysis tests are executed**
5. If Psalm and all other required checks pass ✅
   - GitHub automatically merges the PR to `main`
6. If Psalm or any check fails ❌
   - The PR remains open
   - No auto-merge occurs
   - Manual review and fixes are required

## Protection Mechanism

The auto-merge will **NOT** proceed if:

- ❌ Psalm detects any type errors or issues
- ❌ Any required status check fails
- ❌ Branch protection rules are not met
- ❌ Merge conflicts exist

This ensures that only dependency updates that pass all quality gates (including Psalm static analysis) are automatically merged to the main branch.

## Configuration

The Renovate configuration in `renovate.json` includes:

```json
{
    "$schema": "https://docs.renovatebot.com/renovate-schema.json",
    "extends": [
        "config:recommended"
    ],
    "platformAutomerge": true,
    "major": {
        "dependencyDashboardApproval": true
    }
}
```

The `platformAutomerge: true` setting leverages GitHub's native auto-merge functionality, working in conjunction with your branch protection rules and required status checks to maintain code quality.

## Benefits

- 🚀 Faster dependency updates
- 🛡️ Protected by Psalm static analysis
- ✅ Only merges when all tests pass
- 🔒 Main branch remains stable
- 🔄 Uses GitHub's native auto-merge feature

## Additional Notes

Major version updates require manual approval via the Renovate Dependency Dashboard due to the "dependencyDashboardApproval": true setting for major updates.