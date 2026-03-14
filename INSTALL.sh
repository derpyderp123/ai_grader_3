#!/bin/bash

###############################################################################
# AI-GMS One-Command Installer
# This is a wrapper script that downloads and runs setup.sh
###############################################################################

set -e

echo "=============================================="
echo "  AI-Grading Management System Installer     "
echo "=============================================="
echo ""

# Check if running as root (optional, not required)
if [ "$EUID" -eq 0 ]; then
    echo "⚠️  Running as root is not recommended."
    echo "   The setup will configure permissions for www-data user."
    echo ""
fi

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "📁 Installing to: $SCRIPT_DIR"
echo ""

# Check if setup.sh exists
if [ ! -f "$SCRIPT_DIR/setup.sh" ]; then
    echo "❌ Error: setup.sh not found in $SCRIPT_DIR"
    echo "   Make sure you're running this from the project root."
    exit 1
fi

# Make setup.sh executable
chmod +x "$SCRIPT_DIR/setup.sh"

# Run the setup script
echo "🚀 Starting installation..."
echo ""
"$SCRIPT_DIR/setup.sh"

echo ""
echo "=============================================="
echo "  Installation Complete!                     "
echo "=============================================="
