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
	@echo "make p                 - Run PHP Psalm"
	@echo "make pf FILE=src/Foo.php     - Run PHP Psalm on specific file"
	@echo "make pd DIR=src/           - Run PHP Psalm on directory"
	@echo "make pc                - Clear Psalm's cache"    
        @echo "make cas               - Clear Assets Cache (Safe - preserves .gitignore)"
	@echo "make pi                - Psalm: Show Config/Plugins"
	@echo "make co                - Composer outdated"
	@echo "make cwn REPO=vendor/package VERSION=1.0.0  - Composer why-not"
	@echo "make ccl               - Composer clear-cache & update --lock"
	@echo "make cv                - Composer validate"
	@echo "make cda               - Composer dump-autoload"
	@echo "make cu                - Composer update"
	@echo "make nu                - Update Node modules"
	@echo "make nvm               - Install/Update nvm-windows"
	@echo "make na                - Node: Audit, Clean, List"
	@echo "make crc               - Composer Require Checker"
	@echo "make ct                - Codeception Tests"
	@echo "make cb                - Codeception Build"
	@echo "make rdr               - Rector Dry Run"
	@echo "make rmc               - Rector Make Changes"
	@echo "make csd               - PHP-CS-Fixer Dry Run"
	@echo "make csf               - PHP-CS-Fixer Fix"
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
# Diagnostics
#

ifeq ($(PRIMARY_GOAL),info)
info: ## System Info / Diagnostics
	@echo ".......... SYSTEM DIAGNOSTICS .........."
	php -v
	composer --version
	npm -v
	node -v
	@echo "------------ Composer Platform Check ------------"
	composer check-platform-reqs
	@echo "------------ Node List ------------"
	npm list --depth=0
endif

.PHONY: menu help install p pf pd pc cas pi co cwn ccl cv cda cu nu nvm na crc ct cb rdr rmc csd csf serve ucr uar rl tt ii ist igt iit1 iqt2 ist3 int4 iut5 iait6 info