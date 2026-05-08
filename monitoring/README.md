# Monitoring Stack Installation Scripts

This directory contains installation scripts and configurations for the complete monitoring stack.

## Components

- **node_exporter**: System metrics for Linux/Unix systems
- **windows_exporter**: System metrics for Windows systems  
- **prometheus**: Time series database and scraper
- **grafana**: Visualization and dashboards
- **rfmoz dashboards**: Pre-built Grafana dashboards

## Installation Order

1. Run the appropriate exporter installation script for your OS
2. Install and configure Prometheus using `prometheus.yml`
3. Install Grafana and import dashboard configurations
4. Configure your Yii3 application with Prometheus middleware

## Directory Structure

```
monitoring/
├── install-node-exporter.sh       # Linux/Unix installation
├── install-windows-exporter.ps1   # Windows installation  
├── prometheus.yml                  # Prometheus configuration
├── grafana/                        # Grafana dashboard configs
│   ├── dashboards/                 # JSON dashboard files
│   └── provisioning/               # Auto-provisioning configs
└── docker/                         # Docker compose setup
    └── docker-compose.yml          # Complete stack in containers
```

## Usage

### Linux/Unix Systems
```bash
chmod +x install-node-exporter.sh
./install-node-exporter.sh
```

### Windows Systems  
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
.\install-windows-exporter.ps1
```

### Docker (All Platforms)
```bash
cd docker
docker-compose up -d
```