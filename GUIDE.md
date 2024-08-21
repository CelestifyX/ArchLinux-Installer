# Arch Linux Installation Guide

## Step 1: Boot into the Live Environment
Boot from the Arch Linux installation media.

## Step 2: Set Up the Disk

### 2.1 Partition the Disk
Before running the installation script, partition your disk into two partitions: EFI and System.

1. List your available disks:
    ```bash
    lsblk
    ```

2. Start `cfdisk` on your target disk (replace `/dev/sdX` with your disk):
    ```bash
    cfdisk --zero /dev/sdX
    ```

3. Create a new partition table (select GPT).

4. Create an EFI partition (usually 512MB or more):
- Select `New` → Enter `512M` → Select `Type` → Choose `EFI System`.

5. Create a system partition with the remaining space:
- Select `New` → Use the remaining space → Select `Type` → Choose `Linux filesystem`.

6. Write the changes and exit.

### 2.2 Exit the Live Environment
Once the disk is partitioned, exit `cfdisk` and proceed to run the installation script.

## Step 3: Run the Installation Script

1. Run the installation script to start the setup process:
    ```bash
    ./start
    ```

2. Follow the instructions provided by the script to complete the installation.