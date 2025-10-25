# SonarCloud Integration Setup for Contributors

This project is integrated with SonarCloud for code quality analysis. Follow these steps to set up local SonarCloud integration in VS Code.

## Prerequisites

- VS Code installed
- This project cloned locally
- Access to SonarCloud (free for public repositories)

## Setup Steps

### 1. Install Extension
Install the **SonarQube for IDE** extension in VS Code:
- Open Extensions panel (`Ctrl+Shift+X`)
- Search for "SonarQube for IDE"
- Install the extension by SonarSource

### 2. Connect to SonarCloud

#### Option A: Browser Authentication (Recommended)
1. Open Command Palette (`Ctrl+Shift+P`)
2. Run: `SonarLint: Add SonarQube/SonarCloud Connection`
3. Choose **SonarCloud**
4. Follow browser authentication flow
5. Choose organization: `rossaddison`
6. Give your connection a name (e.g., `your-name-sonarcloud`)

#### Option B: Token Authentication
1. Go to [SonarCloud](https://sonarcloud.io) and log in
2. Go to: My Account → Security → Generate Tokens
3. Create a new token with a descriptive name
4. In VS Code Command Palette: `SonarLint: Add SonarQube/SonarCloud Connection`
5. Choose **SonarCloud** → **Token**
6. Enter your token and organization: `rossaddison`

### 3. Update VS Code Settings
Update `.vscode/settings.json` to match your connection:

```json
{
    "sonarlint.connectedMode.project": {
        "connectionId": "your-connection-name-here",
        "projectKey": "rossaddison_invoice"
    }
}
```

**Important:** 
- Replace `your-connection-name-here` with the connection name you created
- Keep `projectKey` as `rossaddison_invoice`

### 4. Bind Project
1. Run: `SonarLint: Update Project Binding`
2. Confirm the binding to `rossaddison_invoice`

## Verification

Once set up correctly, you should see:
- Real-time code quality issues highlighted in your editor
- Issues matching the SonarCloud project rules
- SonarLint output showing successful connection

## Alternative: Local Analysis Only

If you prefer not to connect to SonarCloud, you can still benefit from local SonarLint analysis:
1. Keep the extension installed
2. Remove the `sonarlint.connectedMode.project` setting
3. SonarLint will use default rules for local analysis

## Project-Specific Configuration

This project also includes:
- **Psalm** static analysis: `php vendor/bin/psalm`
- **SonarQube project config**: `sonar-project.properties`

## Troubleshooting

### Empty Organization Dropdown
- Ensure you're authenticated to SonarCloud
- Try the token authentication method instead

### Connection Issues
- Check SonarLint output panel for error messages
- Verify your SonarCloud account has access to `rossaddison` organization

### No Issues Showing
- Ensure project binding is complete
- Check that your connection name matches the settings.json
- Restart VS Code if needed

## Support

For SonarCloud-specific issues, refer to:
- [SonarLint for VS Code Documentation](https://docs.sonarcloud.io/advanced-setup/sonarlint-smart-notifications/)
- [SonarCloud Documentation](https://docs.sonarcloud.io/)