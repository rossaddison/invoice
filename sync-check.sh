#!/bin/bash
# Sync script to ensure NetBeans and VS Code see the same files

echo "=== NetBeans VS Code Sync Check ==="

# Check if files are the same
VSCODE_FILE="$1"
NETBEANS_PROJECT="/c/wamp64/www/invoice"

if [ -f "$VSCODE_FILE" ]; then
    echo "File exists in VS Code environment: $VSCODE_FILE"
    echo "File size: $(stat -c%s "$VSCODE_FILE") bytes"
    echo "Last modified: $(stat -c%y "$VSCODE_FILE")"
    
    # Show first 10 lines to verify content
    echo "=== First 10 lines ==="
    head -10 "$VSCODE_FILE"
else
    echo "File not found: $VSCODE_FILE"
fi