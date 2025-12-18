# Windows Exporter Installation Script for Windows Systems
# Downloads and installs prometheus-community/windows_exporter for Windows system metrics

param(
    [Parameter(Mandatory=$false)]
    [string]$Version = "0.25.1",
    
    [Parameter(Mandatory=$false)]
    [string]$InstallPath = "C:\Program Files\windows_exporter",
    
    [Parameter(Mandatory=$false)]
    [int]$Port = 9182,
    
    [Parameter(Mandatory=$false)]
    [switch]$Force
)

# Requires elevated privileges
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Error "This script requires Administrator privileges. Please run as Administrator."
    exit 1
}

# Configuration
$ServiceName = "windows_exporter"
$ServiceDisplayName = "Prometheus Windows Exporter"
$ServiceDescription = "Exports Windows system metrics for Prometheus monitoring"
$ConfigPath = "$InstallPath\windows_exporter.yml"

Write-Host "Installing Windows Exporter version $Version..." -ForegroundColor Green

# Create installation directory
if (-not (Test-Path $InstallPath)) {
    New-Item -ItemType Directory -Path $InstallPath -Force | Out-Null
    Write-Host "Created installation directory: $InstallPath" -ForegroundColor Green
}

# Determine architecture
$arch = if ([Environment]::Is64BitOperatingSystem) { "amd64" } else { "386" }
$fileName = "windows_exporter-$Version-$arch.exe"
$downloadUrl = "https://github.com/prometheus-community/windows_exporter/releases/download/v$Version/$fileName"
$exePath = "$InstallPath\windows_exporter.exe"

# Download Windows Exporter
Write-Host "Downloading from: $downloadUrl" -ForegroundColor Yellow
try {
    $webClient = New-Object System.Net.WebClient
    $webClient.DownloadFile($downloadUrl, $exePath)
    Write-Host "Downloaded successfully" -ForegroundColor Green
} catch {
    Write-Error "Failed to download Windows Exporter: $($_.Exception.Message)"
    exit 1
}

# Create configuration file optimized for Yii3 Invoice Application
Write-Host "Creating configuration file..." -ForegroundColor Yellow
$configContent = @"
# Windows Exporter Configuration for Yii3 Invoice Application
# Optimized for web application and database server monitoring

# Collectors Configuration
# Enable collectors that are useful for web application monitoring
collectors:
  # Core system metrics
  cpu: true
  cs: true              # Computer System
  logical_disk: true
  memory: true
  net: true
  os: true
  process: true
  system: true
  
  # Web server and database related
  iis: true             # Internet Information Services
  mssql: true           # SQL Server (if used)
  service: true         # Windows Services
  
  # Application performance
  perfdata: true        # Performance counters
  
  # Disable noisy/unnecessary collectors
  ad: false             # Active Directory
  adcs: false           # Active Directory Certificate Services
  adfs: false           # Active Directory Federation Services
  cache: false          # Cache manager
  container: false      # Windows containers
  dfsr: false           # Distributed File System Replication
  dhcp: false           # DHCP Server
  dns: false            # DNS Server
  exchange: false       # Microsoft Exchange
  fsrmquota: false      # File Server Resource Manager
  hyperv: false         # Hyper-V
  license: false        # Windows license status
  mscluster: false      # Microsoft Failover Cluster
  msmq: false           # Microsoft Message Queuing
  netframework: false   # .NET Framework
  nps: false            # Network Policy Server
  remote_fx: false      # RemoteFX
  scheduled_task: false # Windows Scheduled Tasks
  smb: false            # Server Message Block
  smbclient: false      # SMB Client
  smtp: false           # SMTP Server
  terminal_services: false  # Terminal Services
  textfile: true        # Custom metrics from text files
  thermalzone: false    # Thermal zones
  time: false           # Time synchronization
  vmware: false         # VMware Tools
  
# Service monitoring - specify important services for invoice application
services:
  - "MySQL"             # Database service
  - "Apache2.4"         # Web server
  - "MSSQLSERVER"       # SQL Server (if used)
  - "Redis"             # Cache service (if used)
  - "W3SVC"             # World Wide Web Publishing Service
  - "WAS"               # Windows Process Activation Service

# IIS site monitoring (if using IIS)
iis_sites:
  - "_Total"
  - "Default Web Site"
  - "invoice"           # Your application site

# Performance counters for application monitoring
perfdata_counters:
  - object: "Process"
    instances: ["php-cgi", "mysqld", "httpd", "w3wp"]
    counters: ["% Processor Time", "Working Set", "Private Bytes"]
    
  - object: "Web Service"
    instances: ["_Total"]
    counters: ["Current Connections", "Bytes Received/sec", "Bytes Sent/sec"]
    
  - object: "ASP.NET Applications"
    instances: ["__Total__"]
    counters: ["Requests/Sec", "Request Execution Time"]

# Text file collector directory for custom metrics
textfile_directory: "$InstallPath\textfile_collector"
"@

$configContent | Out-File -FilePath $ConfigPath -Encoding UTF8
Write-Host "Configuration file created: $ConfigPath" -ForegroundColor Green

# Create textfile collector directory and example custom metrics
$textfileDir = "$InstallPath\textfile_collector"
if (-not (Test-Path $textfileDir)) {
    New-Item -ItemType Directory -Path $textfileDir -Force | Out-Null
}

$customMetricsContent = @"
# Custom metrics for Yii3 Invoice Application
# This file can be updated by your application or scheduled tasks

# Example: Last backup timestamp
yii3_invoice_last_backup_timestamp 0

# Example: Database size metrics (update via scheduled task)
yii3_invoice_database_size_bytes 0

# Example: Application uptime
yii3_invoice_app_uptime_seconds 0

# Example: License status
yii3_invoice_license_valid 1
"@

$customMetricsContent | Out-File -FilePath "$textfileDir\yii3_custom.prom" -Encoding UTF8

# Stop existing service if it exists
if (Get-Service -Name $ServiceName -ErrorAction SilentlyContinue) {
    Write-Host "Stopping existing $ServiceName service..." -ForegroundColor Yellow
    Stop-Service -Name $ServiceName -Force
    sc.exe delete $ServiceName | Out-Null
    Start-Sleep -Seconds 3
}

# Install as Windows Service
Write-Host "Installing Windows Service..." -ForegroundColor Yellow

$serviceArgs = @(
    "--web.listen-address=:$Port"
    "--config.file=`"$ConfigPath`""
    "--collector.textfile.directory=`"$textfileDir`""
    "--collector.service.services-where=`"Name LIKE 'MySQL%' OR Name LIKE 'Apache%' OR Name LIKE 'MSSQL%' OR Name LIKE 'Redis%' OR Name='W3SVC' OR Name='WAS'`""
    "--web.max-requests=40"
    "--log.level=info"
)

$servicePath = "`"$exePath`" " + ($serviceArgs -join " ")

# Create the service
$service = New-Service -Name $ServiceName -DisplayName $ServiceDisplayName -Description $ServiceDescription -BinaryPathName $servicePath -StartupType Automatic

if ($service) {
    Write-Host "Service created successfully" -ForegroundColor Green
} else {
    Write-Error "Failed to create service"
    exit 1
}

# Configure service recovery options
Write-Host "Configuring service recovery options..." -ForegroundColor Yellow
sc.exe failure $ServiceName reset=86400 actions=restart/30000/restart/60000/restart/120000 | Out-Null

# Start the service
Write-Host "Starting Windows Exporter service..." -ForegroundColor Yellow
try {
    Start-Service -Name $ServiceName
    Write-Host "Service started successfully" -ForegroundColor Green
} catch {
    Write-Error "Failed to start service: $($_.Exception.Message)"
    exit 1
}

# Wait for service to start
Start-Sleep -Seconds 5

# Test the metrics endpoint
Write-Host "Testing metrics endpoint..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:$Port/metrics" -UseBasicParsing -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Write-Host "Metrics endpoint is responding correctly" -ForegroundColor Green
        Write-Host "First few lines of metrics:" -ForegroundColor Cyan
        ($response.Content -split "`n" | Select-Object -First 5) | ForEach-Object { Write-Host "  $_" -ForegroundColor Gray }
    }
} catch {
    Write-Warning "Metrics endpoint test failed, but service may still be starting: $($_.Exception.Message)"
}

# Create firewall rule for the port
Write-Host "Creating firewall rule for port $Port..." -ForegroundColor Yellow
try {
    New-NetFirewallRule -DisplayName "Windows Exporter" -Direction Inbound -Protocol TCP -LocalPort $Port -Action Allow -Profile Any | Out-Null
    Write-Host "Firewall rule created successfully" -ForegroundColor Green
} catch {
    Write-Warning "Failed to create firewall rule: $($_.Exception.Message)"
    Write-Host "You may need to manually allow port $Port through Windows Firewall" -ForegroundColor Yellow
}

# Create scheduled task to update custom metrics (example)
Write-Host "Creating scheduled task for custom metrics..." -ForegroundColor Yellow
$taskAction = New-ScheduledTaskAction -Execute "PowerShell" -Argument "-WindowStyle Hidden -Command `"& { Get-Date | Out-File -FilePath '$textfileDir\yii3_custom.prom' -Append }`""
$taskTrigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 5) -Once -At (Get-Date)
$taskSettings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
$taskPrincipal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

try {
    Register-ScheduledTask -TaskName "WindowsExporterCustomMetrics" -Action $taskAction -Trigger $taskTrigger -Settings $taskSettings -Principal $taskPrincipal -Description "Updates custom metrics for Windows Exporter" | Out-Null
    Write-Host "Scheduled task created successfully" -ForegroundColor Green
} catch {
    Write-Warning "Failed to create scheduled task: $($_.Exception.Message)"
}

# Display installation summary
Write-Host "`n" -NoNewline
Write-Host "========================================" -ForegroundColor Green
Write-Host "Windows Exporter Installation Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Service Details:" -ForegroundColor Cyan
Write-Host "  Name: $ServiceName"
Write-Host "  Display Name: $ServiceDisplayName"
Write-Host "  Port: $Port"
Write-Host "  Status: " -NoNewline
$serviceStatus = (Get-Service -Name $ServiceName).Status
if ($serviceStatus -eq "Running") {
    Write-Host $serviceStatus -ForegroundColor Green
} else {
    Write-Host $serviceStatus -ForegroundColor Red
}
Write-Host ""
Write-Host "Endpoints:" -ForegroundColor Cyan
Write-Host "  Metrics: http://localhost:$Port/metrics"
Write-Host "  Health:  http://localhost:$Port/health"
Write-Host ""
Write-Host "Configuration Files:" -ForegroundColor Cyan
Write-Host "  Service Binary: $exePath"
Write-Host "  Configuration: $ConfigPath"
Write-Host "  Custom Metrics: $textfileDir\yii3_custom.prom"
Write-Host ""
Write-Host "Service Management:" -ForegroundColor Cyan
Write-Host "  Start:   Start-Service -Name $ServiceName"
Write-Host "  Stop:    Stop-Service -Name $ServiceName"
Write-Host "  Status:  Get-Service -Name $ServiceName"
Write-Host "  Logs:    Get-WinEvent -FilterHashtable @{LogName='Application'; ProviderName='windows_exporter'} -MaxEvents 20"
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Add this target to your Prometheus configuration:"
Write-Host "   targets: ['localhost:$Port']"
Write-Host "2. Restart Prometheus to start scraping metrics"
Write-Host "3. Import Windows monitoring dashboards in Grafana"
Write-Host "4. Configure alerts for critical system metrics"
Write-Host ""
Write-Host "Integration with Yii3 Invoice Application:" -ForegroundColor Yellow
Write-Host "- IIS metrics will show web server performance"
Write-Host "- Process metrics will monitor PHP and database processes"
Write-Host "- Service metrics will track MySQL, Apache, and other dependencies"
Write-Host "- Custom metrics can be updated by your application in:"
Write-Host "  $textfileDir\"
Write-Host ""

# Test Prometheus integration
Write-Host "Testing Prometheus integration..." -ForegroundColor Cyan
Write-Host "You can verify the installation by:"
Write-Host "1. Opening http://localhost:$Port/metrics in your browser"
Write-Host "2. Checking that metrics are being collected"
Write-Host "3. Configuring Prometheus to scrape this endpoint"