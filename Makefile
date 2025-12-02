# Error message formatting functions
define print_error
	@echo "\033[0;31m❌ ERROR:\033[0m $(1)"
	@echo ""
endef

define print_success
	@echo "\033[0;32m✅ SUCCESS:\033[0m $(1)"
endef

define print_info
	@echo "\033[0;36mℹ️  INFO:\033[0m $(1)"
endef

.PHONY: help
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.PHONY: check-ddev
check-ddev: ## Verify DDEV is installed and functional
	@if ! command -v ddev >/dev/null 2>&1; then \
		echo "\033[0;31m❌ ERROR:\033[0m DDEV is not installed or not in your PATH"; \
		echo ""; \
		echo "This project requires DDEV for local development."; \
		echo ""; \
		echo "📦 Installation Instructions:"; \
		echo "   Visit: https://ddev.readthedocs.io/en/stable/users/install/"; \
		echo ""; \
		echo "   Platform-specific quick install:"; \
		echo "   • macOS:    brew install ddev/ddev/ddev"; \
		echo "   • Windows:  choco install ddev"; \
		echo "   • Linux:    See installation guide above"; \
		echo ""; \
		echo "After installing DDEV, run 'make setup' again."; \
		exit 1; \
	fi; \
	DDEV_OUTPUT=$$(ddev version 2>&1); \
	DDEV_EXIT_CODE=$$?; \
	if [ $$DDEV_EXIT_CODE -ne 0 ] || echo "$$DDEV_OUTPUT" | grep -Eiq "cannot connect|docker.*(not|n't).*(running|available|found)|failed.*docker|error.*docker|docker daemon.*not"; then \
		echo "\033[0;31m❌ ERROR:\033[0m DDEV is installed but cannot connect to Docker"; \
		echo ""; \
		echo "DDEV requires Docker to be running."; \
		echo ""; \
		echo "🐳 Troubleshooting Steps:"; \
		echo "   1. Start Docker Desktop (macOS/Windows) or Docker daemon (Linux)"; \
		echo "   2. Wait for Docker to fully start (check system tray icon)"; \
		echo "   3. Run 'ddev poweroff' to reset DDEV state"; \
		echo "   4. Run 'make setup' again"; \
		echo ""; \
		echo "If issues persist, try:"; \
		echo "   • Restart Docker completely"; \
		echo "   • Run 'ddev debug test' for detailed diagnostics"; \
		echo ""; \
		if [ $$DDEV_EXIT_CODE -ne 0 ]; then \
			echo "Error output:"; \
			echo "$$DDEV_OUTPUT"; \
		fi; \
		exit 1; \
	fi; \
	DDEV_VERSION=$$(echo "$$DDEV_OUTPUT" | grep -i "ddev version"); \
	echo "\033[0;32m✅ SUCCESS:\033[0m DDEV is ready"; \
	echo ""; \
	echo "$$DDEV_VERSION"

.PHONY: setup
setup: check-ddev ## Install dependencies, generate app key, run migrations, and build assets
	ddev composer run setup

.PHONY: dev
dev: check-ddev ## Launch DDEV, run migrations, and start Vite dev server
	ddev launch
	ddev php artisan migrate
	ddev exec npm run dev

.PHONY: build
build: check-ddev ## Build production assets with Vite
	ddev exec npm run build

.DEFAULT_GOAL := help
