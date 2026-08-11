.DEFAULT_GOAL := menu

CLI_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
$(eval $(CLI_ARGS):;@:)

PRIMARY_GOAL := $(if $(MAKECMDGOALS),$(firstword $(MAKECMDGOALS)),menu)

#
# Menu / Help Targets
#

ifeq ($(PRIMARY_GOAL),menu)
menu: ## Show the Invoice SYSTEM MENU (Make targets)
	@echo "================================================================================"
	@echo "                 Invoice SYSTEM MENU (Make targets)"
	@echo "================================================================================"
	@echo "make install           - Composer and NPM install (or calls install.bat if found)"
	@echo "make ext-check         - Check required PHP extensions (pre-install)"
	@echo "make ext-json          - Check extensions with JSON output"
	@echo "make ext-silent        - Check extensions silently (exit code only)"
	@echo "make p                 - Run PHP Psalm"
	@echo "make pf FILE=src/Foo.php     - Run PHP Psalm on specific file"
	@echo "make pd DIR=src/           - Run PHP Psalm on directory"
	@echo "make pc                - Clear Psalm's cache"
	@echo "make pi                - Psalm: Show Config/Plugins"
	@echo "make cas               - Clear Assets Cache (Safe - preserves .gitignore)"
	@echo "make co                - Composer outdated"
	@echo "make cwn REPO=vendor/package VERSION=1.0.0  - Composer why-not"
	@echo "make ccl               - Composer clear-cache & update --lock"
	@echo "make cv                - Composer validate"
	@echo "make cda               - Composer dump-autoload"
	@echo "make ca                - Composer audit"
	@echo "make cu                - Composer update"
	@echo "make naf               - npm: Audit Fix"
	@echo "make nu                - Update Node modules"
	@echo "make nco               - npm: Check Outdated"
	@echo "make nsu               - npm: Safe Update (patch only)"
	@echo "make nmu               - npm: Minor Update (minor versions)"
	@echo "make nma               - npm: Major Update (interactive)"
	@echo "make nes2024           - npm: ES2024 Feature Verification"
	@echo "make nvm               - Install/Update nvm-windows"
	@echo "make na                - Node: Audit, Clean, List"
	@echo "make tsb               - TypeScript Build (Production)"
	@echo "make tsd               - TypeScript Build (Development)"
	@echo "make tsw               - TypeScript Watch Mode"
	@echo "make tst               - TypeScript Type Check"
	@echo "make tsl               - TypeScript Lint"
	@echo "make tsf               - TypeScript Format"
	@echo "make nb                - npm run build"
	@echo "make crc               - Composer Require Checker"
	@echo "make sda               - Shipmonk Composer Dependency Analyser"
	@echo "make ct                - PHPUnit Tests (Tests/Unit/Invoice/Entity/)"
	@echo "make cta               - PHPUnit Tests (All: Tests/Unit/)"
	@echo "make ctp               - PHPUnit Tests (Functional/Integration/PHPUnit/)"
	@echo "make ccf               - Codeception Functional Suite"
	@echo "make cca               - Codeception Acceptance Suite (needs browser driver)"
	@echo "make cc                - Codeception All Suites"
	@echo "make te                - Testo: All Suites (Tests/Testo/ + src/)"
	@echo "make teu               - Testo: Unit Suite (Tests/Testo/)"
	@echo "make tes               - Testo: Sources Suite (inline tests)"
	@echo "make rdr               - Rector Dry Run"
	@echo "make rmc               - Rector Make Changes"
	@echo "make csd               - PHP-CS-Fixer Dry Run"
	@echo "make csf               - PHP-CS-Fixer Fix"
	@echo "make si                - [SETUP 1] Install Snyk CLI"
	@echo "make sa                - [SETUP 2] Snyk Authenticate (browser login)"
	@echo "make sw                - [SETUP 3] Verify Snyk auth (whoami)"
	@echo "make sq                - Snyk Security Check (Quick - High Severity Only)"
	@echo "make sf                - Snyk Security Check (Full - Code + Dependencies)"
	@echo "make sd                - Snyk Security Check (Dependencies Only)"
	@echo "make pcs               - PHP CodeSniffer: Check 85-char line length"
	@echo "make pcsf FILE=src/Foo.php - PHP CodeSniffer: Check specific file"
	@echo "make pcsd DIR=src/         - PHP CodeSniffer: Check specific directory"
	@echo "make pcsr              - PHP CodeSniffer: Full report with details"
	@echo "make sc FILE=path/to/file      - Snyk Security Code Check on Specific File"
	@echo "make ss                - Snyk Security Summary (Total Issues Count Only)"
	@echo "make sj                - Snyk Security JSON Output (Machine Readable)"
	@echo "make sh                - Snyk Security High Severity Only"
	@echo "make sr                - Snyk Full Scan + Save to snyk-report.txt"
	@echo "make ghi               - Install GitHub CLI"
	@echo "make gha               - GitHub CLI Auth Status"
	@echo "make ghc               - GitHub CLI Copilot Version Check"
	@echo "make serve             - PHP Built-in serve"
	@echo "make ucr USERNAME= user PASSWORD= pass      - user/create"
	@echo "make uar ROLE=admin USERID=1                - user/assignRole"
	@echo "make rl                - router/list"
	@echo "make rlc CONTROLLER=Inv                     - router/list --controller=<name>"
	@echo "make tt TEXT=abc LANG=fr                    - translator/translate"
	@echo "make ii                - invoice/items"
	@echo "make cpv               - system/check-php-version"
	@echo "make ist               - invoice/setting/truncate"
	@echo "make igt               - invoice/generator/truncate"
	@echo "make iit1              - invoice/inv/truncate1"
	@echo "make iqt2              - invoice/quote/truncate2"
	@echo "make ist3              - invoice/salesorder/truncate3"
	@echo "make int4              - invoice/nonuserrelated/truncate4"
	@echo "make iut5              - invoice/userrelated/truncate5"
	@echo "make iait6             - invoice/autoincrementsettooneafter/truncate6"
	@echo "make sonar SONAR_TOKEN=xxx               - SonarCloud: All open issues"
	@echo "make sonar-pr PR=862 SONAR_TOKEN=xxx     - SonarCloud: Issues on a PR"
	@echo "make sonar-type TYPE=BUG SONAR_TOKEN=xxx - SonarCloud: Filter by type (BUG/VULNERABILITY/CODE_SMELL)"
	@echo "make sonar-sev SEV=MAJOR SONAR_TOKEN=xxx - SonarCloud: Filter by severity (BLOCKER/CRITICAL/MAJOR/MINOR/INFO)"
	@echo "make sonar-hot SONAR_TOKEN=xxx           - SonarCloud: Security hotspots"
	@echo "make sonar-both TYPE=BUG SEV=MAJOR SONAR_TOKEN=xxx - SonarCloud: Type + severity"
	@echo "make sonar-rule RULE=php:S1192 SONAR_TOKEN=xxx     - SonarCloud: Filter by rule key"
	@echo "make sonar-file FILE=src/Foo.php SONAR_TOKEN=xxx   - SonarCloud: Filter by file path"
	@echo "make sonar-rely SONAR_TOKEN=xxx                    - SonarCloud: Reliability issues (BUG)"
	@echo "make sonar-rely-grp SONAR_TOKEN=xxx                - SonarCloud: Reliability grouped by rule"
	@echo "make sonar-all-grp SONAR_TOKEN=xxx                 - SonarCloud: All issues grouped by rule"
	@echo "make sonar-lang LANG=php SONAR_TOKEN=xxx           - SonarCloud: Filter by language"
	@echo "(Tip: 'export SONAR_TOKEN=xxx' in your shell once to avoid repeating it)"
	@echo "make peppol-check                         - Check Peppol code-list XML currency against OpenPEPPOL GitHub"
	@echo "(Tip: 'export GITHUB_TOKEN=xxx' to raise API rate limit from 60 to 5000/hr)"
	@echo "make ba                - Benchmarks: Run All Suites (saves to history.json)"
	@echo "make bdi               - Benchmarks: DI Container Suite"
	@echo "make binj              - Benchmarks: Injector Suite"
	@echo "make brt               - Benchmarks: Router Suite"
	@echo "make bst               - Benchmarks: String Helpers Suite"
	@echo "make bdr               - Benchmarks: Dry Run (no save)"
	@echo "make bdb               - Benchmarks: Serve Dashboard (localhost:8080)"
	@echo "make info              - System Info/Diagnostics"
	@echo "make dli               - System: Download Menu Icons"
	@echo "make csk               - System: Generate COOKIE_SECRET_KEY (.env)"
	@echo ""
	@echo "make help              - Show summary of commands"
	@echo ""
endif

ifeq ($(PRIMARY_GOAL),help)
help: ## This help.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
endif

#
# Installation
#

ifeq ($(PRIMARY_GOAL),install)
install: ## Composer and NPM install (or calls install.bat if found)
	@if [ -f install.bat ]; then \
		echo "[INFO] install.bat found. Running install.bat..."; \
		bash install.bat || ./install.bat; \
	else \
		echo "[INFO] No install.bat found. Running composer & npm install..."; \
		composer install; \
		npm install; \
	fi
endif

#
# Extension Checker
#

ifeq ($(PRIMARY_GOAL),ext-check)
ext-check: ## Check required PHP extensions (based on invoice_build.yml)
	@echo "================================================================================"
	@echo "              PHP Extension Checker (Pre-Installation)"
	@echo "================================================================================"
	@echo "Checking required PHP extensions for Invoice System..."
	@echo "Based on invoice_build.yml workflow requirements"
	@echo ""
	@php scripts/extension-checker.php
	@echo ""
	@echo "[INFO] If extensions are missing, follow the instructions above."
	@echo "[INFO] You may need to restart WAMP/Apache after making changes."
endif

ifeq ($(PRIMARY_GOAL),ext-json)
ext-json: ## Check extensions and output JSON format
	@php scripts/extension-checker.php --json
endif

ifeq ($(PRIMARY_GOAL),ext-silent)
ext-silent: ## Check extensions silently (exit code only)
	@php scripts/extension-checker.php --silent
endif

#
# Psalm
#

ifeq ($(PRIMARY_GOAL),p)
p: ## Run PHP Psalm
	php vendor/bin/psalm
endif

ifeq ($(PRIMARY_GOAL),pf)
pf: ## Run PHP Psalm on a specific file
ifndef FILE
	$(error Please provide FILE, e.g. 'make pf FILE=src/Foo.php')
endif
	php vendor/bin/psalm "$(FILE)"
endif

ifeq ($(PRIMARY_GOAL),pd)
pd: ## Run PHP Psalm on a directory
ifndef DIR
	$(error Please provide DIR, e.g. 'make pd DIR=src/')
endif
	php vendor/bin/psalm "$(DIR)"
endif

ifeq ($(PRIMARY_GOAL),pc)
pc: ## Clear Psalm's cache
	php vendor/bin/psalm --clear-cache
endif

ifeq ($(PRIMARY_GOAL),pi)
pi: ## Psalm: Show Config/Plugins
	php vendor/bin/psalm --show-info || echo Psalm version does not support --show-info
endif

#
# Assets Management
#

ifeq ($(PRIMARY_GOAL),cas)
cas: ## Clear Assets Cache (Safe - preserves .gitignore)
	@echo "Clearing assets cache while preserving .gitignore..."
ifeq ($(OS),Windows_NT)
	powershell -Command "Get-ChildItem -Path 'public/assets' -Exclude '.gitignore' | Remove-Item -Recurse -Force"
else
	find public/assets -mindepth 1 -not -name '.gitignore' -exec rm -rf {} +
endif
	@echo "Assets cache cleared successfully (preserved .gitignore)"
endif

#
# Composer
#

ifeq ($(PRIMARY_GOAL),co)
co: ## Composer outdated
	composer outdated
endif

ifeq ($(PRIMARY_GOAL),cwn)
cwn: ## Composer why-not
ifndef REPO
	$(error Please provide REPO, e.g. 'make cwn REPO=vendor/package VERSION=1.0.0')
endif
ifndef VERSION
	$(error Please provide VERSION, e.g. 'make cwn REPO=vendor/package VERSION=1.0.0')
endif
	composer why-not $(REPO) $(VERSION)
endif

ifeq ($(PRIMARY_GOAL),ccl)
ccl: ## Composer clear-cache & update --lock
	composer clear-cache
	composer update --lock
endif

ifeq ($(PRIMARY_GOAL),cv)
cv: ## Composer validate
	composer validate
endif

ifeq ($(PRIMARY_GOAL),cda)
cda: ## Composer dump-autoload
	composer dump-autoload -o
endif

ifeq ($(PRIMARY_GOAL),ca)
ca: ## Composer audit
	composer audit --ansi
endif

ifeq ($(PRIMARY_GOAL),cu)
cu: ## Composer update
	composer update
endif

#
# Node / NPM
#

ifeq ($(PRIMARY_GOAL),nu)
nu: ## Update Node modules
	npx npm-check-updates -u
	npm install
endif

ifeq ($(PRIMARY_GOAL),nco)
nco: ## npm: Check Outdated (like 'composer outdated')
	npm run upgrade:check
endif

ifeq ($(PRIMARY_GOAL),nsu)
nsu: ## npm: Safe Update (patch only)
	npm run upgrade:safe
endif

ifeq ($(PRIMARY_GOAL),nmu)
nmu: ## npm: Minor Update (minor versions)
	npm run upgrade:minor
endif

ifeq ($(PRIMARY_GOAL),nma)
nma: ## npm: Major Update (interactive)
	npm run upgrade:major
endif

ifeq ($(PRIMARY_GOAL),nes2024)
nes2024: ## npm: ES2024 Feature Verification
	npm run es2024:verify
endif

ifeq ($(PRIMARY_GOAL),nvm)
nvm: ## Install/Update nvm-windows
	powershell -Command "Invoke-WebRequest -Uri https://github.com/coreybutler/nvm-windows/releases/latest/download/nvm-setup.exe -OutFile nvm-setup.exe"
	start /wait nvm-setup.exe /SILENT
	rm -f nvm-setup.exe
endif

ifeq ($(PRIMARY_GOAL),na)
na: ## Node: Audit, Clean, List
	npm audit
	npm cache clean --force
	npm list --depth=0
endif

ifeq ($(PRIMARY_GOAL),naf)
naf: ## npm: Audit Fix
	npm audit fix
endif

#
# TypeScript Build System
#

ifeq ($(PRIMARY_GOAL),tsb)
tsb: ## TypeScript Build (Production)
	npm run build:prod
endif

ifeq ($(PRIMARY_GOAL),tsd)
tsd: ## TypeScript Build (Development)
	npm run build:dev
endif

ifeq ($(PRIMARY_GOAL),tsw)
tsw: ## TypeScript Watch Mode (Development)
	npm run build:watch
endif

ifeq ($(PRIMARY_GOAL),tst)
tst: ## TypeScript Type Check
	npm run type-check
endif

ifeq ($(PRIMARY_GOAL),tsl)
tsl: ## TypeScript Lint
	npm run lint
endif

ifeq ($(PRIMARY_GOAL),tsf)
tsf: ## TypeScript Format Check + Fix
	npm run format:check && npm run format
endif

ifeq ($(PRIMARY_GOAL),nb)
nb: ## npm run build
	npm run build
endif

#
# Composer Tools
#

ifeq ($(PRIMARY_GOAL),crc)
crc: ## Composer Require Checker
	php -d memory_limit=512M vendor/bin/composer-require-checker
endif

ifeq ($(PRIMARY_GOAL),sda)
sda: ## Shipmonk Composer Dependency Analyser
	@echo "Running Shipmonk Composer Dependency Analyser..."
	@echo "(https://github.com/shipmonk-rnd/composer-dependency-analyser)"
	php vendor/bin/composer-dependency-analyser
endif

#
# PHPUnit / Codeception
#

ifeq ($(PRIMARY_GOAL),ct)
ct: ## PHPUnit Tests (Tests/Unit/Invoice/Entity/)
	php vendor/bin/phpunit Tests/Unit/Invoice/Entity/ --no-coverage --testdox
endif

ifeq ($(PRIMARY_GOAL),cta)
cta: ## PHPUnit Tests (All: Tests/Unit/)
	php vendor/bin/phpunit Tests/Unit/ --no-coverage --testdox
endif

ifeq ($(PRIMARY_GOAL),ctp)
ctp: ## PHPUnit Tests (Functional/Integration/PHPUnit/)
	php vendor/bin/phpunit Tests/Functional/ Tests/Integration/ Tests/PHPUnit/ --no-coverage --testdox
endif

ifeq ($(PRIMARY_GOAL),ccf)
ccf: ## Codeception Functional Suite
	php vendor/bin/codecept run Functional
endif

ifeq ($(PRIMARY_GOAL),cca)
cca: ## Codeception Acceptance Suite (requires running server + browser driver)
	@echo "[INFO] Requires: php yii serve (running) + Selenium/Playwright browser driver"
	php vendor/bin/codecept run Acceptance
endif

ifeq ($(PRIMARY_GOAL),cc)
cc: ## Codeception All Suites
	@echo "[INFO] Acceptance suite requires: php yii serve (running) + browser driver"
	php vendor/bin/codecept run
endif

#
# Testo
#

ifeq ($(PRIMARY_GOAL),te)
te: ## Testo: All Suites (Tests/Testo/ + src/)
	php vendor/bin/testo
endif

ifeq ($(PRIMARY_GOAL),teu)
teu: ## Testo: Unit Suite (Tests/Testo/)
	php vendor/bin/testo --suite=Unit
endif

ifeq ($(PRIMARY_GOAL),tes)
tes: ## Testo: Sources Suite (inline tests)
	php vendor/bin/testo --suite=Sources
endif

#
# Rector & PHP-CS-Fixer
#

ifeq ($(PRIMARY_GOAL),rdr)
rdr: ## Rector Dry Run
	php vendor/bin/rector process --dry-run --output-format=console
endif

ifeq ($(PRIMARY_GOAL),rmc)
rmc: ## Rector Make Changes
	php vendor/bin/rector
endif

ifeq ($(PRIMARY_GOAL),csd)
csd: ## PHP-CS-Fixer Dry Run
	php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --show-progress=bar --verbose
endif

ifeq ($(PRIMARY_GOAL),csf)
csf: ## PHP-CS-Fixer Fix
	php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php
endif

#
# Security Analysis
#

ifeq ($(PRIMARY_GOAL),si)
si: ## [SETUP 1] Install Snyk CLI
	npm install -g snyk
endif

ifeq ($(PRIMARY_GOAL),sa)
sa: ## [SETUP 2] Snyk Authenticate (opens browser login)
	snyk auth
endif

ifeq ($(PRIMARY_GOAL),sw)
sw: ## [SETUP 3] Verify Snyk auth (whoami)
	snyk whoami
endif

ifeq ($(PRIMARY_GOAL),sq)
sq: ## Snyk Security Check (Quick - High Severity Only)
	npm run security:quick
endif

ifeq ($(PRIMARY_GOAL),sf)
sf: ## Snyk Security Check (Full - Code + Dependencies)
	npm run security:full
endif

ifeq ($(PRIMARY_GOAL),sd)
sd: ## Snyk Security Check (Dependencies Only)
	npm run security:deps
endif

ifeq ($(PRIMARY_GOAL),sc)
sc: ## Snyk Security Code Check on Specific File
ifndef FILE
	$(error Please provide FILE, e.g. 'make sc FILE=src/Invoice/Inv/InvController.php')
endif
	snyk code test --file="$(FILE)"
endif

ifeq ($(PRIMARY_GOAL),ss)
ss: ## Snyk Security Summary (Total Issues Count Only)
ifeq ($(OS),Windows_NT)
	snyk code test | findstr /C:"Total issues"
else
	snyk code test | grep "Total issues"
endif
endif

ifeq ($(PRIMARY_GOAL),sj)
sj: ## Snyk Security JSON Output (Machine Readable)
	snyk code test --json
endif

ifeq ($(PRIMARY_GOAL),sh)
sh: ## Snyk Security High Severity Only
	snyk code test --severity-threshold=high
endif

ifeq ($(PRIMARY_GOAL),sr)
sr: ## Snyk Full Scan + Save to snyk-report.txt
	snyk code test | tee snyk-report.txt
endif

#
# GitHub CLI
#

ifeq ($(PRIMARY_GOAL),ghi)
ghi: ## Install GitHub CLI
	@echo "Installing GitHub CLI..."
	@if command -v gh >/dev/null 2>&1; then \
		echo "[INFO] GitHub CLI is already installed."; \
		gh --version; \
	else \
		if command -v winget >/dev/null 2>&1; then \
			winget install --id GitHub.cli; \
		elif command -v brew >/dev/null 2>&1; then \
			brew install gh; \
		elif command -v apk >/dev/null 2>&1; then \
			apk add --no-cache github-cli; \
		elif command -v apt-get >/dev/null 2>&1; then \
			echo "Installing GitHub CLI via official script..."; \
			curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg && \
			sudo chmod go+r /usr/share/keyrings/githubcli-archive-keyring.gpg && \
			echo "deb [arch=$$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null && \
			sudo apt-get update && \
			sudo apt-get install gh -y; \
		else \
			echo "[ERROR] No supported package manager found."; \
			echo "Please install manually from https://cli.github.com/"; \
		fi; \
	fi
endif

ifeq ($(PRIMARY_GOAL),gha)
gha: ## GitHub CLI Auth Status
	@command -v gh >/dev/null 2>&1 || (echo "[ERROR] GitHub CLI not installed. Run 'make ghi' first." && exit 1)
	@gh auth status
endif

ifeq ($(PRIMARY_GOAL),ghc)
ghc: ## GitHub CLI Copilot Version Check
	@command -v gh >/dev/null 2>&1 || (echo "[ERROR] GitHub CLI not installed. Run 'make ghi' first." && exit 1)
	@echo "Checking Copilot access..."
	@gh api user/copilot_seat_details 2>/dev/null && \
		(echo "✓ Copilot access confirmed" && \
		 echo "" && \
		 echo "Manage subscription: https://github.com/settings/copilot") || \
		(echo "✗ No Copilot subscription found via API" && \
		 echo "" && \
		 echo "If you have a subscription but it's not detected:" && \
		 echo "  1. Check authenticated account: gh auth status" && \
		 echo "  2. Verify subscription: https://github.com/settings/copilot" && \
		 echo "  3. Try re-authenticating: gh auth login" && \
		 echo "" && \
		 echo "If you need Copilot access:" && \
		 echo "  - Individual: https://github.com/features/copilot" && \
		 echo "  - Organization: Contact your GitHub admin")
	@echo ""
	@gh --version
endif

#
# Yii Console Commands
#

ifeq ($(PRIMARY_GOAL),serve)
serve: ## PHP Built-in serve
	php yii serve
endif

ifeq ($(PRIMARY_GOAL),ucr)
ucr: ## user/create USERNAME PASSWORD
ifndef USERNAME
	$(error Please provide USERNAME, e.g. 'make ucr USERNAME=admin PASSWORD=admin')
endif
ifndef PASSWORD
	$(error Please provide PASSWORD, e.g. 'make ucr USERNAME=admin PASSWORD=admin')
endif
	php yii user/create "$(USERNAME)" "$(PASSWORD)"
endif

ifeq ($(PRIMARY_GOAL),uar)
uar: ## user/assignRole ROLE USERID
ifndef ROLE
	$(error Please provide ROLE, e.g. 'make uar ROLE=admin USERID=1')
endif
ifndef USERID
	$(error Please provide USERID, e.g. 'make uar ROLE=admin USERID=1')
endif
	php yii user/assignRole "$(ROLE)" "$(USERID)"
endif

ifeq ($(PRIMARY_GOAL),rl)
rl: ## router/list
	php yii router/list
endif

ifeq ($(PRIMARY_GOAL),rlc)
rlc: ## router/list --controller=NAME (adds the Controller column if blank)
	php yii router/list --controller="$(CONTROLLER)"
endif

ifeq ($(PRIMARY_GOAL),tt)
tt: ## translator/translate TEXT LANG
ifndef TEXT
	$(error Please provide TEXT, e.g. 'make tt TEXT=hello LANG=fr')
endif
ifndef LANG
	$(error Please provide LANG, e.g. 'make tt TEXT=hello LANG=fr')
endif
	php yii translator/translate "$(TEXT)" "$(LANG)"
endif

ifeq ($(PRIMARY_GOAL),ii)
ii: ## invoice/items
	php yii invoice/items
endif

ifeq ($(PRIMARY_GOAL),cpv)
cpv: ## system/check-php-version
	php yii system/check-php-version
endif

ifeq ($(PRIMARY_GOAL),ist)
ist: ## invoice/setting/truncate
	php yii invoice/setting/truncate
endif

ifeq ($(PRIMARY_GOAL),igt)
igt: ## invoice/generator/truncate
	php yii invoice/generator/truncate
endif

ifeq ($(PRIMARY_GOAL),iit1)
iit1: ## invoice/inv/truncate1
	php yii invoice/inv/truncate1
endif

ifeq ($(PRIMARY_GOAL),iqt2)
iqt2: ## invoice/quote/truncate2
	php yii invoice/quote/truncate2
endif

ifeq ($(PRIMARY_GOAL),ist3)
ist3: ## invoice/salesorder/truncate3
	php yii invoice/salesorder/truncate3
endif

ifeq ($(PRIMARY_GOAL),int4)
int4: ## invoice/nonuserrelated/truncate4
	php yii invoice/nonuserrelated/truncate4
endif

ifeq ($(PRIMARY_GOAL),iut5)
iut5: ## invoice/userrelated/truncate5
	php yii invoice/userrelated/truncate5
endif

ifeq ($(PRIMARY_GOAL),iait6)
iait6: ## invoice/autoincrementsettooneafter/truncate6
	php yii invoice/autoincrementsettooneafter/truncate6
endif

#
# SonarCloud Issues
#

ifeq ($(PRIMARY_GOAL),sonar)
sonar: ## SonarCloud: All open issues (export SONAR_TOKEN first, or pass as make var)
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN, e.g. 'make sonar SONAR_TOKEN=your-token' or 'export SONAR_TOKEN=your-token')
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php
endif

ifeq ($(PRIMARY_GOAL),sonar-pr)
sonar-pr: ## SonarCloud: Issues on a specific PR (usage: make sonar-pr PR=862 SONAR_TOKEN=xxx)
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN, e.g. 'make sonar-pr PR=862 SONAR_TOKEN=your-token')
endif
ifndef PR
	$(error Please provide PR, e.g. 'make sonar-pr PR=862 SONAR_TOKEN=your-token')
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --pr=$(PR)
endif

ifeq ($(PRIMARY_GOAL),sonar-type)
sonar-type: ## SonarCloud: Filter by type (TYPE=BUG|VULNERABILITY|CODE_SMELL)
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN)
endif
ifndef TYPE
	$(error Please provide TYPE=BUG, TYPE=VULNERABILITY, or TYPE=CODE_SMELL)
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --type=$(TYPE)
endif

ifeq ($(PRIMARY_GOAL),sonar-sev)
sonar-sev: ## SonarCloud: Filter by severity (SEV=BLOCKER|CRITICAL|MAJOR|MINOR|INFO)
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN)
endif
ifndef SEV
	$(error Please provide SEV=BLOCKER, SEV=CRITICAL, SEV=MAJOR, SEV=MINOR, or SEV=INFO)
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --severity=$(SEV)
endif

ifeq ($(PRIMARY_GOAL),sonar-hot)
sonar-hot: ## SonarCloud: Security hotspots
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN)
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --hotspots
endif

ifeq ($(PRIMARY_GOAL),sonar-both)
sonar-both: ## SonarCloud: Filter by type + severity (TYPE=... SEV=...)
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN)
endif
ifndef TYPE
	$(error Please provide TYPE=BUG, TYPE=VULNERABILITY, or TYPE=CODE_SMELL)
endif
ifndef SEV
	$(error Please provide SEV=BLOCKER, SEV=CRITICAL, SEV=MAJOR, SEV=MINOR, or SEV=INFO)
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --type=$(TYPE) --severity=$(SEV)
endif

ifeq ($(PRIMARY_GOAL),sonar-rule)
sonar-rule: ## SonarCloud: Filter by rule key (usage: make sonar-rule RULE=php:S1192 SONAR_TOKEN=xxx)
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN)
endif
ifndef RULE
	$(error Please provide RULE, e.g. 'make sonar-rule RULE=php:S1192 SONAR_TOKEN=your-token')
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --rule=$(RULE)
endif

ifeq ($(PRIMARY_GOAL),sonar-file)
sonar-file: ## SonarCloud: Filter by file path (usage: make sonar-file FILE=src/Invoice/Inv/InvController.php SONAR_TOKEN=xxx)
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN)
endif
ifndef FILE
	$(error Please provide FILE, e.g. 'make sonar-file FILE=src/Invoice/Inv/InvController.php SONAR_TOKEN=your-token')
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --file=$(FILE)
endif

ifeq ($(PRIMARY_GOAL),sonar-rely)
sonar-rely: ## SonarCloud: Reliability issues (BUG)
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN)
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --type=BUG
endif

ifeq ($(PRIMARY_GOAL),sonar-rely-grp)
sonar-rely-grp: ## SonarCloud: Reliability issues grouped by rule
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN)
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --type=BUG --grouped
endif

ifeq ($(PRIMARY_GOAL),sonar-all-grp)
sonar-all-grp: ## SonarCloud: All issues grouped by rule
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN)
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --grouped
endif

ifeq ($(PRIMARY_GOAL),sonar-lang)
sonar-lang: ## SonarCloud: Filter by language (usage: make sonar-lang LANG=php SONAR_TOKEN=xxx)
ifndef SONAR_TOKEN
	$(error Please provide SONAR_TOKEN)
endif
ifndef LANG
	$(error Please provide LANG=typescript, LANG=php, LANG=javascript, LANG=css, or LANG=xml)
endif
	SONAR_TOKEN=$(SONAR_TOKEN) php sonar-issues.php --language=$(LANG)
endif

#
# Diagnostics
#

ifeq ($(PRIMARY_GOAL),info)
info: ## System Info / Diagnostics
	@echo ".......... SYSTEM DIAGNOSTICS .........."
	php -v
	composer --version
	npm -v
	node -v
	npx tsc --version
	@echo "------------ Composer Platform Check ------------"
	composer check-platform-reqs
	@echo "------------ Node List ------------"
	npm list --depth=0
endif

ifeq ($(PRIMARY_GOAL),dli)
dli: ## System: Download Menu Icons
	php bin/download-cli-icons.php
endif

ifeq ($(PRIMARY_GOAL),csk)
csk: ## System: Generate COOKIE_SECRET_KEY (.env)
	php -r "echo bin2hex(random_bytes(32));"
endif

#
# PHP CodeSniffer Line Length Checking (85 characters)
#

ifeq ($(PRIMARY_GOAL),pcs)
pcs: ## Run PHP CodeSniffer to check 85-character line length
	@echo "Checking PHP files for 85-character line length limit..."
	php -d memory_limit=1024M vendor/bin/phpcs --standard=phpcs.xml.dist
endif

ifeq ($(PRIMARY_GOAL),pcsf)
pcsf: ## Run PHP CodeSniffer on specific file (usage: make pcsf FILE=src/Invoice.php)
ifndef FILE
	$(error Please provide FILE, e.g. 'make pcsf FILE=src/Invoice/Invoice.php')
endif
	@echo "Checking $(FILE) for 85-character line length..."
	php -d memory_limit=1024M vendor/bin/phpcs --standard=Generic --sniffs=Generic.Files.LineLength \
		--runtime-set lineLimit 85 --runtime-set absoluteLineLimit 85 $(FILE)
endif

ifeq ($(PRIMARY_GOAL),pcsd)
pcsd: ## Run PHP CodeSniffer on specific directory (usage: make pcsd DIR=src/)
ifndef DIR
	$(error Please provide DIR, e.g. 'make pcsd DIR=src/')
endif
	@echo "Checking $(DIR) for 85-character line length..."
	php -d memory_limit=1024M vendor/bin/phpcs --standard=Generic --sniffs=Generic.Files.LineLength \
		--runtime-set lineLimit 85 --runtime-set absoluteLineLimit 85 $(DIR)
endif

ifeq ($(PRIMARY_GOAL),pcsr)
pcsr: ## Run PHP CodeSniffer with detailed report
	@echo "Running detailed line length report..."
	php -d memory_limit=1024M vendor/bin/phpcs --standard=phpcs.xml.dist --report=full --report-width=120
endif

#
# Performance Benchmarks
#

ifeq ($(PRIMARY_GOAL),ba)
ba: ## Benchmarks: Run All Suites (saves to history.json)
	php benchmarks/run.php
endif

ifeq ($(PRIMARY_GOAL),bdi)
bdi: ## Benchmarks: DI Container Suite
	php benchmarks/run.php --suite=di
endif

ifeq ($(PRIMARY_GOAL),binj)
binj: ## Benchmarks: Injector Suite
	php benchmarks/run.php --suite=injector
endif

ifeq ($(PRIMARY_GOAL),brt)
brt: ## Benchmarks: Router Suite
	php benchmarks/run.php --suite=router
endif

ifeq ($(PRIMARY_GOAL),bst)
bst: ## Benchmarks: String Helpers Suite
	php benchmarks/run.php --suite=strings
endif

ifeq ($(PRIMARY_GOAL),bdr)
bdr: ## Benchmarks: Dry Run (no save)
	php benchmarks/run.php --dry-run
endif

ifeq ($(PRIMARY_GOAL),bdb)
bdb: ## Benchmarks: Serve Dashboard (localhost:8080)
	php -S localhost:8080 -t benchmarks
endif

#
# Peppol
#

ifeq ($(PRIMARY_GOAL),peppol-check)
peppol-check: ## Check Peppol code-list XML currency against OpenPEPPOL GitHub
ifdef GITHUB_TOKEN
	GITHUB_TOKEN=$(GITHUB_TOKEN) php bin/check-peppol-codelists.php
else
	php bin/check-peppol-codelists.php
endif
endif

.PHONY: menu help install ext-check ext-json ext-silent p pf pd pc pi cas co cwn ccl cv cda ca cu nu naf nco nsu nmu nma nes2024 nvm na crc sda ct cta ctp ccf cca cc te teu tes rdr rmc csd csf si sa sw sq sf sd sc ss sj sh sr ghi gha ghc serve ucr uar rl rlc tt ii cpv ist igt iit1 iqt2 ist3 int4 iut5 iait6 info dli csk tsb tsd tsw tst tsl tsf nb pcs pcsf pcsd pcsr sonar sonar-pr sonar-type sonar-sev sonar-hot sonar-both sonar-rule sonar-file sonar-rely sonar-rely-grp sonar-all-grp sonar-lang peppol-check ba bdi binj brt bst bdr bdb