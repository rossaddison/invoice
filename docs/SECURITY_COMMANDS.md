# Snyk Security Analysis Commands

This document describes the new Snyk security analysis commands added to both the Makefile and m.bat menu system.

## New Snyk Commands Added

### 1. Snyk Security Summary (Issues Count Only)
- **Makefile**: `make ss`
- **Batch Menu**: Option `[5j]`
- **Direct Command**: `snyk code test | findstr /C:"Total issues"`
- **Purpose**: Quickly see just the total number of security issues without detailed output

### 2. Snyk Security JSON Output (Machine Readable)
- **Makefile**: `make sj`
- **Batch Menu**: Option `[5k]`
- **Direct Command**: `snyk code test --json`
- **Purpose**: Get machine-readable JSON output for integration with other tools or scripts

### 3. Snyk Security High Severity Only
- **Makefile**: `make sh`
- **Batch Menu**: Option `[5l]`
- **Direct Command**: `snyk code test --severity-threshold=high`
- **Purpose**: Show only HIGH severity security issues, filtering out medium and low priority items

## Usage Examples

### Quick Check (Snyk Summary Only)
```bash
# Using Makefile (if make is available)
make ss

# Using batch file
m.bat
# Then select option 5j (Snyk Security Summary)

# Direct command
snyk code test | findstr /C:"Total issues"
```

### High Priority Issues Only (Snyk)
```bash
# Using Makefile
make sh

# Using batch file  
m.bat
# Then select option 5l (Snyk Security High Severity Only)

# Direct command
snyk code test --severity-threshold=high
```

### Machine Readable Output (Snyk JSON)
```bash
# Using Makefile
make sj

# Using batch file
m.bat  
# Then select option 5k (Snyk Security JSON Output)

# Direct command
snyk code test --json
```

## Current Security Status

As of the last scan:
- **Total Issues**: 2 (down from 54)
- **High Severity**: 1 
- **Medium Severity**: 1
- **Low Severity**: 0

## Integration with Existing Commands

These new commands complement the existing Snyk security analysis tools:

- `make sq` / Option `[5f]` - Snyk Security Check (Quick - High Severity Only via npm script)
- `make sf` / Option `[5g]` - Snyk Security Check (Full - Code + Dependencies via npm script)
- `make sd` / Option `[5h]` - Snyk Security Check (Dependencies Only via npm script)
- `make sc FILE=path` / Option `[5i]` - Snyk Security Code Check on Specific File

## Notes

- All commands require Snyk CLI to be installed and authenticated
- The batch file (`m.bat`) provides an interactive menu system for Windows users
- The Makefile provides command-line shortcuts for Unix-like environments or when using make on Windows
- Commands are designed for local development and continuous integration workflows