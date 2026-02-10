.DEFAULT_GOAL := menu

CLI_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
$(eval $(CLI_ARGS):;@:)

PRIMARY_GOAL := $(firstword $(MAKECMDGOALS))

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
	@echo "make cu                - Composer update"
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
	@echo "make ct                - Codeception Tests"
	@echo "make cb                - Codeception Build"
	@echo "make rdr               - Rector Dry Run"
	@echo "make rmc               - Rector Make Changes"
	@echo "make csd               - PHP-CS-Fixer Dry Run"
	@echo "make csf               - PHP-CS-Fixer Fix"
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
	@echo "make serve             - PHP Built-in serve"
	@echo "make ucr USERNAME= user PASSWORD= pass      - user/create"
	@echo "make uar ROLE=admin USERID=1                - user/assignRole"
	@echo "make rl                - router/list"
	@echo "make tt TEXT=abc LANG=fr                    - translator/translate"
	@echo "make ii                - invoice/items"
	@echo "make ist               - invoice/setting/truncate"
	@echo "make igt               - invoice/generator/truncate"
	@echo "make iit1              - invoice/inv/truncate1"
	@echo "make iqt2              - invoice/quote/truncate2"
	@echo "make ist3              - invoice/salesorder/truncate3"
	@echo "make int4              - invoice/nonuserrelated/truncate4"
	@echo "make iut5              - invoice/userrelated/truncate5"
	@echo "make iait6             - invoice/autoincrementsettooneafter/truncate6"
	@echo "make info              - System Info/Diagnostics"
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
tsf: ## TypeScript Format
	npm run format
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

#
# Codeception
#

ifeq ($(PRIMARY_GOAL),ct)
ct: ## Codeception Tests
	php vendor/bin/codecept run
endif

ifeq ($(PRIMARY_GOAL),cb)
cb: ## Codeception Build
	php vendor/bin/codecept build
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
	php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --diff
endif

ifeq ($(PRIMARY_GOAL),csf)
csf: ## PHP-CS-Fixer Fix
	php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php
endif

#
# Security Analysis
#

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
	snyk code test | findstr /C:"Total issues"
endif

ifeq ($(PRIMARY_GOAL),sj)
sj: ## Snyk Security JSON Output (Machine Readable)
	snyk code test --json
endif

ifeq ($(PRIMARY_GOAL),sh)
sh: ## Snyk Security High Severity Only
	snyk code test --severity-threshold=high
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

#
# PHP CodeSniffer Line Length Checking (85 characters)
#

ifeq ($(PRIMARY_GOAL),pcs)
pcs: ## Run PHP CodeSniffer to check 85-character line length
	@echo "Checking PHP files for 85-character line length limit..."
	php vendor/bin/phpcs --standard=phpcs.xml.dist
endif

ifeq ($(PRIMARY_GOAL),pcsf)
pcsf: ## Run PHP CodeSniffer on specific file (usage: make pcsf FILE=src/Invoice.php)
ifndef FILE
	$(error Please provide FILE, e.g. 'make pcsf FILE=src/Invoice/Invoice.php')
endif
	@echo "Checking $(FILE) for 85-character line length..."
	php vendor/bin/phpcs --standard=Generic --sniffs=Generic.Files.LineLength \
		--runtime-set lineLimit 85 --runtime-set absoluteLineLimit 85 $(FILE)
endif

ifeq ($(PRIMARY_GOAL),pcsd)
pcsd: ## Run PHP CodeSniffer on specific directory (usage: make pcsd DIR=src/)
ifndef DIR
	$(error Please provide DIR, e.g. 'make pcsd DIR=src/')
endif
	@echo "Checking $(DIR) for 85-character line length..."
	php vendor/bin/phpcs --standard=Generic --sniffs=Generic.Files.LineLength \
		--runtime-set lineLimit 85 --runtime-set absoluteLineLimit 85 $(DIR)
endif

ifeq ($(PRIMARY_GOAL),pcsr)
pcsr: ## Run PHP CodeSniffer with detailed report
	@echo "Running detailed line length report..."
	php vendor/bin/phpcs --standard=phpcs.xml.dist --report=full --report-width=120
endif

.PHONY: menu help install p pf pd pc pi cas co cwn ccl cv cda cu nu nco nsu nmu nma nes2024 nvm na crc ct cb rdr rmc csd csf sq sf sd sc ss sj sh serve ucr uar rl tt ii ist igt iit1 iqt2 ist3 int4 iut5 iait6 info tsb tsd tsw tst tsl tsf nb pcs pcsf pcsd pcsr