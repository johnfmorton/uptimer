# Troubleshooting Guide

This guide covers common issues you might encounter when working with the Kiro Laravel Skeleton and how to resolve them.

## DDEV Installation Issues

### DDEV Not Found

If you see an error message indicating DDEV is not installed:

**Error Message:**
```
❌ ERROR: DDEV is not installed or not in your PATH
```

**Solution:**

1. Install DDEV using the official installation guide: <https://ddev.readthedocs.io/en/stable/users/install/>

2. Platform-specific quick install commands:
   * **macOS**: `brew install ddev/ddev/ddev`
   * **Windows**: `choco install ddev`
   * **Linux**: See the installation guide above for distribution-specific instructions

3. After installation, verify DDEV is working:
   ```bash
   ddev version
   ```

4. Run your original command again (e.g., `make setup`)

### Docker Not Running

If DDEV is installed but Docker is not running:

**Error Message:**
```
❌ ERROR: DDEV is installed but cannot connect to Docker
```

**Solution:**

1. **Start Docker**:
   * **macOS/Windows**: Launch Docker Desktop from your Applications folder or system tray
   * **Linux**: Start the Docker daemon with `sudo systemctl start docker`

2. **Wait for Docker to fully start**: Check the Docker Desktop icon in your system tray - it should show "Docker Desktop is running"

3. **Reset DDEV state**:
   ```bash
   ddev poweroff
   ```

4. **Run your original command again** (e.g., `make setup`)

**If issues persist:**

* Restart Docker completely (quit and relaunch Docker Desktop)
* Run DDEV diagnostics:
  ```bash
  ddev debug test
  ```
* Check Docker is running:
  ```bash
  docker ps
  ```

### DDEV Version Issues

If you encounter errors related to DDEV functionality:

1. Check your DDEV version:
   ```bash
   ddev version
   ```

2. Update DDEV to the latest version:
   * **macOS**: `brew upgrade ddev`
   * **Windows**: `choco upgrade ddev`
   * **Linux**: Follow the upgrade instructions in the DDEV documentation

3. After upgrading, restart your project:
   ```bash
   ddev restart
   ```

## Advanced: Skipping DDEV Checks

For advanced users who need to bypass the automatic DDEV checks (not recommended for normal development):

You can run the underlying commands directly without the Makefile:

```bash
# Instead of: make setup
ddev composer install
ddev artisan key:generate
ddev artisan migrate
ddev npm install
ddev npm run build

# Instead of: make dev
ddev start
ddev artisan migrate
ddev npm run dev
```

**Warning**: Skipping checks means you won't receive helpful error messages if DDEV or Docker are not properly configured.

## Manual DDEV Check

You can manually verify your DDEV installation at any time:

```bash
make check-ddev
```

This will display:

* Whether DDEV is installed
* The installed DDEV version
* Whether Docker is accessible
* Any configuration issues

## Getting Additional Help

If you continue to experience issues:

1. Check the [DDEV documentation](https://ddev.readthedocs.io/)
2. Review the [Laravel documentation](https://laravel.com/docs)
3. Open an issue on the project repository with details about your environment and the error you're encountering
