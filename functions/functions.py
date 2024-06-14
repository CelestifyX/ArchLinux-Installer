import os
import subprocess
import sys
import random
import string
import time

from colors.colors import Colors
from status.status import Status

def clear_screen():
    os.system('clear')

def terminate_installation():
    print(f'\n{Colors.bold}{Colors.red}CRITICAL{Colors.reset}: Installation aborted.')
    sys.exit()

def execute_command(
    command,
    no_visible=False
):
    try:
        if no_visible:
            result = subprocess.run(command, shell=True, check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        else:
            result = subprocess.run(command, shell=True, check=True)

        return result.returncode
    except subprocess.CalledProcessError as e:
        print(f'\nAn error has occurred: {e}')
        return e.returncode

def validate_choice(
    prompt,
    valid_choices,
    default_to_one=False
):
    choice = None

    while choice not in valid_choices:
        choice = input(prompt).strip()

        if not choice and default_to_one:
            choice = "1"
            break

        if choice not in valid_choices:
            print(f'{Colors.red}ERROR{Colors.reset}: Invalid choice. Please try again.')

    return choice

def validate_input(
    prompt,
    valid_choices,
    message
):
    choice = None

    while True:
        choice = input(prompt).strip().lower()

        if choice in valid_choices:
            print(message.replace('%valid%', choice))
        else:
            break

    return choice

def validate_timezone(
    prompt,
    message
):
    while True:
        choice = input(prompt).strip()

        if not choice:
            return "UTC"

        try:
            result    = subprocess.run(['timedatectl', 'list-timezones'], capture_output=True, text=True)
            timezones = result.stdout.split('\n')

            if choice in timezones:
                return choice
            else:
                print(message.replace('%timezone%', choice))
        except Exception as e:
            print(f'\nAn error has occurred: {e}')
            return False

def validate_device(
    prompt,
    message_not_found,
    message_empty
):
    while True:
        choice = input(prompt).strip()

        if not choice:
            print(message_empty)
            continue

        try:
            result = subprocess.run(['lsblk', '-o', 'NAME', '-l', f'/dev/{choice}'], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
            device = result.stdout.split('\n')[1:]

            if choice in device:
                return choice
            else:
                print(message_not_found.replace('%device%', choice))
        except Exception as e:
            print(f'\nAn error has occurred: {e}')
            return False

def get_size(
    device,
    partition,
    isPartition=True
):
    if isPartition:
        command = f'fdisk -l /dev/{device} | grep /dev/{partition}'
        output  = subprocess.check_output(command, shell=True, text=True)
        lines   = output.split('\n')

        for line in lines:
            if device in line:
                return line.split()[4]
    else:
        command = f'fdisk -l /dev/{device} | grep "Disk /dev/{device}"'
        output  = subprocess.check_output(command, shell=True, text=True)

        return output.split('\n')[0].split(":")[1].split(",")[0].strip()

def generate_password(length=8):
    characters = (string.ascii_letters + string.digits)
    password   = ''.join(random.choice(characters) for _ in range(length))

    return password

def check_package_exists(package):
    try:
        subprocess.run(['pacman', '-Si', package], check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        return True
    except subprocess.CalledProcessError:
        return False

def read_additional_packages():
    try:
        with open('additional_packages.txt', 'r') as file:
            packages = [line.strip() for line in file if line.strip()]

        return packages
    except IOError:
        return []

def get_existing_packages():
    return [package for package in read_additional_packages() if check_package_exists(package)]

def print_progress(description):
    message = f'{Status.PROGRESS} {description}'
    print(message, end='', flush=True)

def print_result(
    return_code,
    description,
    end_time
):
    if return_code == 0:
        message = f'{Status.OK} {description}'
    else:
        message = f'{Status.ERROR} {description}'

    print(f'\r{message} [{end_time:.2f}s]')

def execute_and_process_command(
    command,
    description
):
    start_time = time.time()
    print_progress(description)

    try:
        with open('installer.log', 'a') as log_file:
            subprocess.run(command, shell=True, check=True, stdout=log_file, stderr=subprocess.DEVNULL)
    except subprocess.CalledProcessError:
        end_time = time.time()
        print_result(1, description, (end_time - start_time))
    else:
        end_time = time.time()

    print_result(0, description, (end_time - start_time))
    return True
