#!/bin/bash

# Node Exporter Installation Script for Linux/Unix Systems
# Downloads and installs Prometheus node_exporter for system metrics collection

set -e

# Configuration
NODE_EXPORTER_VERSION="1.7.0"
NODE_EXPORTER_USER="node_exporter"
NODE_EXPORTER_GROUP="node_exporter"
INSTALL_DIR="/opt/node_exporter"
SERVICE_DIR="/etc/systemd/system"
CONFIG_DIR="/etc/node_exporter"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

echo_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

echo_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   echo_error "This script should not be run as root for security reasons"
   echo "Please run as a regular user with sudo privileges"
   exit 1
fi

# Detect architecture
ARCH=$(uname -m)
case $ARCH in
    x86_64)
        ARCH="amd64"
        ;;
    aarch64)
        ARCH="arm64"
        ;;
    armv7l)
        ARCH="armv7"
        ;;
    *)
        echo_error "Unsupported architecture: $ARCH"
        exit 1
        ;;
esac

echo_info "Installing Node Exporter version $NODE_EXPORTER_VERSION for $ARCH"

# Create user and group
echo_info "Creating node_exporter user and group..."
sudo groupadd --system $NODE_EXPORTER_GROUP 2>/dev/null || true
sudo useradd --system --gid $NODE_EXPORTER_GROUP --shell /bin/false --home /nonexistent $NODE_EXPORTER_USER 2>/dev/null || true

# Create directories
echo_info "Creating directories..."
sudo mkdir -p $INSTALL_DIR
sudo mkdir -p $CONFIG_DIR

# Download and extract node_exporter
DOWNLOAD_URL="https://github.com/prometheus/node_exporter/releases/download/v${NODE_EXPORTER_VERSION}/node_exporter-${NODE_EXPORTER_VERSION}.linux-${ARCH}.tar.gz"
TEMP_DIR=$(mktemp -d)

echo_info "Downloading node_exporter from $DOWNLOAD_URL"
cd $TEMP_DIR
wget -q $DOWNLOAD_URL
tar -xzf "node_exporter-${NODE_EXPORTER_VERSION}.linux-${ARCH}.tar.gz"

# Install binary
echo_info "Installing node_exporter binary..."
sudo cp "node_exporter-${NODE_EXPORTER_VERSION}.linux-${ARCH}/node_exporter" $INSTALL_DIR/
sudo chown root:root $INSTALL_DIR/node_exporter
sudo chmod 755 $INSTALL_DIR/node_exporter

# Create configuration file
echo_info "Creating configuration file..."
sudo tee $CONFIG_DIR/node_exporter.yml > /dev/null <<EOF
# Node Exporter Configuration for Yii3 Invoice Application
# This configuration optimizes metrics collection for web application monitoring

# Text file collector directory (optional)
# Place custom metrics files here for collection
textfile_collector_directory: "$CONFIG_DIR/textfile_collector"

# Collector configuration
collectors:
  # Enable essential collectors for web application monitoring
  cpu: true
  diskstats: true
  filesystem: true
  loadavg: true
  meminfo: true
  netdev: true
  netstat: true
  stat: true
  time: true
  uname: true
  vmstat: true
  
  # Database-related collectors (useful for invoice application)
  systemd: true  # For monitoring database services
  processes: true  # For PHP-FPM, MySQL processes
  
  # Web server collectors
  textfile: true  # For custom application metrics
  
  # Disable noisy collectors
  arp: false
  bcache: false
  bonding: false
  conntrack: false
  edac: false
  entropy: false
  fibrechannel: false
  hwmon: false
  infiniband: false
  ipvs: false
  mdadm: false
  nfs: false
  nfsd: false
  sockstat: false
  xfs: false
  zfs: false
EOF

# Create systemd service file
echo_info "Creating systemd service..."
sudo tee $SERVICE_DIR/node_exporter.service > /dev/null <<EOF
[Unit]
Description=Node Exporter for Prometheus
Documentation=https://prometheus.io/docs/guides/node-exporter/
Wants=network-online.target
After=network-online.target

[Service]
Type=simple
User=$NODE_EXPORTER_USER
Group=$NODE_EXPORTER_GROUP
ExecStart=$INSTALL_DIR/node_exporter \\
  --web.listen-address=:9100 \\
  --path.rootfs=/ \\
  --collector.textfile.directory=$CONFIG_DIR/textfile_collector \\
  --collector.filesystem.mount-points-exclude="^/(sys|proc|dev|host|etc|rootfs/var/lib/docker/containers|rootfs/var/lib/docker/overlay2|rootfs/run/docker/netns|rootfs/var/lib/docker/aufs)($$|/)" \\
  --collector.filesystem.fs-types-exclude="^(autofs|binfmt_misc|bpf|cgroup2?|configfs|debugfs|devpts|devtmpfs|fusectl|hugetlbfs|iso9660|mqueue|nsfs|overlay|proc|procfs|pstore|rpc_pipefs|securityfs|selinuxfs|squashfs|sysfs|tracefs)$$" \\
  --no-collector.arp \\
  --no-collector.bcache \\
  --no-collector.bonding \\
  --no-collector.conntrack \\
  --no-collector.edac \\
  --no-collector.entropy \\
  --no-collector.fibrechannel \\
  --no-collector.hwmon \\
  --no-collector.infiniband \\
  --no-collector.ipvs \\
  --no-collector.mdadm \\
  --no-collector.nfs \\
  --no-collector.nfsd \\
  --no-collector.sockstat \\
  --no-collector.xfs \\
  --no-collector.zfs

SyslogIdentifier=node_exporter
Restart=always
RestartSec=1
StartLimitInterval=0

# Security settings
NoNewPrivileges=yes
ProtectHome=yes
ProtectSystem=strict
ProtectControlGroups=true
ProtectKernelModules=true
ProtectKernelTunables=yes
RestrictRealtime=yes
RestrictSUIDSGID=yes
RemoveIPC=yes
RestrictNamespaces=yes

[Install]
WantedBy=multi-user.target
EOF

# Create textfile collector directory
sudo mkdir -p $CONFIG_DIR/textfile_collector
sudo chown -R $NODE_EXPORTER_USER:$NODE_EXPORTER_GROUP $CONFIG_DIR
sudo chmod 755 $CONFIG_DIR
sudo chmod 755 $CONFIG_DIR/textfile_collector

# Create custom metrics example for Yii3 application
sudo tee $CONFIG_DIR/textfile_collector/yii3_custom.prom > /dev/null <<EOF
# Custom metrics for Yii3 Invoice Application
# This file can be updated by your application to expose custom metrics

# Example: Last successful backup timestamp
yii3_invoice_last_backup_timestamp 0

# Example: Total invoices in database (update via cron job)
yii3_invoice_database_invoices_total 0

# Example: Application deployment timestamp  
yii3_invoice_deployment_timestamp 0
EOF

sudo chown $NODE_EXPORTER_USER:$NODE_EXPORTER_GROUP $CONFIG_DIR/textfile_collector/yii3_custom.prom

# Enable and start service
echo_info "Enabling and starting node_exporter service..."
sudo systemctl daemon-reload
sudo systemctl enable node_exporter
sudo systemctl start node_exporter

# Wait for service to start
sleep 3

# Check service status
if sudo systemctl is-active --quiet node_exporter; then
    echo_info "Node Exporter installed and started successfully!"
    echo_info "Service is running on port 9100"
    echo_info "Metrics available at: http://localhost:9100/metrics"
    
    # Test metrics endpoint
    echo_info "Testing metrics endpoint..."
    if curl -s http://localhost:9100/metrics | head -n 5; then
        echo_info "Metrics endpoint is responding correctly"
    else
        echo_warn "Metrics endpoint test failed, but service is running"
    fi
else
    echo_error "Failed to start node_exporter service"
    sudo systemctl status node_exporter
    exit 1
fi

# Cleanup
rm -rf $TEMP_DIR

echo_info "Installation completed successfully!"
echo ""
echo "Next steps:"
echo "1. Add this target to your Prometheus configuration:"
echo "   targets: ['localhost:9100']"
echo "2. Restart Prometheus to start scraping metrics"
echo "3. Check Grafana for system metrics dashboards"
echo ""
echo "Configuration files:"
echo "  - Service: $SERVICE_DIR/node_exporter.service"
echo "  - Config: $CONFIG_DIR/node_exporter.yml"
echo "  - Custom metrics: $CONFIG_DIR/textfile_collector/"
echo ""
echo "Service management:"
echo "  - Start:   sudo systemctl start node_exporter"
echo "  - Stop:    sudo systemctl stop node_exporter"
echo "  - Status:  sudo systemctl status node_exporter"
echo "  - Logs:    sudo journalctl -u node_exporter -f"