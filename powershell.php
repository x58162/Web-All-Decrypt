<?php

/*
 * ============================================================
 * all-decrypt.com
 * PowerShell Security Diagnostic Endpoint
 *
 * 使用方式：
 *
 * https://all-decrypt.com/powershell.php?action=test
 * https://all-decrypt.com/powershell.php?action=user
 * https://all-decrypt.com/powershell.php?action=system
 * https://all-decrypt.com/powershell.php?action=security
 *
 * 注意：
 * 本檔案只回傳固定、白名單式 PowerShell 診斷程式碼。
 * 不接受任意 PowerShell 指令。
 * ============================================================
 */

header('Content-Type: text/plain; charset=utf-8');

$action = $_GET['action'] ?? 'test';


switch ($action) {


    /*
     * ========================================================
     * TEST
     * ========================================================
     */
    case 'test':

        echo <<<'PS'
Write-Host "================================"
Write-Host "Hello from all-decrypt.com!"
Write-Host "PowerShell server test"
Write-Host "Time: $(Get-Date)"
Write-Host "Computer: $env:COMPUTERNAME"
Write-Host "User: $env:USERNAME"
Write-Host "================================"
PS;

        break;


    /*
     * ========================================================
     * USER
     * ========================================================
     */
    case 'user':

        echo <<<'PS'
Write-Host "================================"
Write-Host "Windows User Information"
Write-Host "================================"

Write-Host ""

Write-Host "Current User:"
try {
    whoami
}
catch {
    Write-Host "Unable to execute whoami."
}

Write-Host ""

Write-Host "User Name:"
Write-Host $env:USERNAME

Write-Host ""

Write-Host "Computer Name:"
Write-Host $env:COMPUTERNAME

Write-Host ""

Write-Host "User Domain:"
Write-Host $env:USERDOMAIN

Write-Host ""

Write-Host "Logon Server:"
Write-Host $env:LOGONSERVER

Write-Host ""

Write-Host "================================"
Write-Host "User Test Finished"
Write-Host "================================"
PS;

        break;


    /*
     * ========================================================
     * SYSTEM
     * ========================================================
     */
    case 'system':

        echo <<<'PS'
Write-Host "================================"
Write-Host "Windows System Information"
Write-Host "================================"

Write-Host ""

Write-Host "Computer:"
Write-Host $env:COMPUTERNAME

Write-Host ""

Write-Host "User:"
Write-Host $env:USERNAME

Write-Host ""

Write-Host "PowerShell Version:"
$PSVersionTable.PSVersion

Write-Host ""

Write-Host "PowerShell Edition:"
Write-Host $PSVersionTable.PSEdition

Write-Host ""

Write-Host "Windows Information:"

try {

    Get-CimInstance Win32_OperatingSystem |
        Select-Object `
            Caption,
            Version,
            OSArchitecture |
        Format-List

}
catch {

    Write-Host "Unable to read Windows information."

}

Write-Host ""

Write-Host "================================"
Write-Host "System Test Finished"
Write-Host "================================"
PS;

        break;


    /*
     * ========================================================
     * SECURITY
     * ========================================================
     */
    case 'security':

        echo <<<'PS'
Write-Host ""
Write-Host "=================================================="
Write-Host "       WINDOWS SECURITY DIAGNOSTIC"
Write-Host "=================================================="
Write-Host ""



# ============================================================
# 1. COMPUTER / USER
# ============================================================

Write-Host "=================================================="
Write-Host "1. COMPUTER / USER"
Write-Host "=================================================="

Write-Host ""

Write-Host "Computer Name:"
Write-Host $env:COMPUTERNAME

Write-Host ""

Write-Host "Current User:"

try {
    whoami
}
catch {
    Write-Host "Unable to execute whoami."
}

Write-Host ""

Write-Host "User Name:"
Write-Host $env:USERNAME

Write-Host ""

Write-Host "User Domain:"
Write-Host $env:USERDOMAIN

Write-Host ""



# ============================================================
# 2. WINDOWS VERSION
# ============================================================

Write-Host "=================================================="
Write-Host "2. WINDOWS VERSION"
Write-Host "=================================================="

Write-Host ""

try {

    $os = Get-CimInstance Win32_OperatingSystem

    Write-Host "Caption:"
    Write-Host $os.Caption

    Write-Host ""

    Write-Host "Version:"
    Write-Host $os.Version

    Write-Host ""

    Write-Host "Architecture:"
    Write-Host $os.OSArchitecture

}
catch {

    Write-Host "Unable to read Windows information."

}

Write-Host ""



# ============================================================
# 3. POWERSHELL
# ============================================================

Write-Host "=================================================="
Write-Host "3. POWERSHELL"
Write-Host "=================================================="

Write-Host ""

Write-Host "PowerShell Version:"
Write-Host $PSVersionTable.PSVersion

Write-Host ""

Write-Host "PowerShell Edition:"
Write-Host $PSVersionTable.PSEdition

Write-Host ""

Write-Host "PowerShell Version Table:"
$PSVersionTable |
    Format-List

Write-Host ""



# ============================================================
# 4. POWERSHELL EXECUTION POLICY
# ============================================================

Write-Host "=================================================="
Write-Host "4. POWERSHELL EXECUTION POLICY"
Write-Host "=================================================="

Write-Host ""

try {

    Get-ExecutionPolicy -List |
        Format-Table -AutoSize

}
catch {

    Write-Host "Unable to read Execution Policy."

}

Write-Host ""



# ============================================================
# 5. RDP SERVICE
# ============================================================

Write-Host "=================================================="
Write-Host "5. RDP SERVICE"
Write-Host "=================================================="

Write-Host ""

$rdpService = Get-Service `
    -Name TermService `
    -ErrorAction SilentlyContinue

if ($null -eq $rdpService) {

    Write-Host "TermService: NOT FOUND"

}
else {

    Write-Host "Service Name:"
    Write-Host $rdpService.Name

    Write-Host ""

    Write-Host "Display Name:"
    Write-Host $rdpService.DisplayName

    Write-Host ""

    Write-Host "Status:"
    Write-Host $rdpService.Status

    Write-Host ""

    Write-Host "Start Type:"
    Write-Host $rdpService.StartType

}

Write-Host ""



# ============================================================
# 6. RDP REGISTRY
# ============================================================

Write-Host "=================================================="
Write-Host "6. RDP REGISTRY"
Write-Host "=================================================="

Write-Host ""

$rdpRegistry = Get-ItemProperty `
    -Path "HKLM:\SYSTEM\CurrentControlSet\Control\Terminal Server" `
    -Name "fDenyTSConnections" `
    -ErrorAction SilentlyContinue

if ($null -eq $rdpRegistry) {

    Write-Host "Unable to read RDP registry."

}
else {

    Write-Host "fDenyTSConnections:"
    Write-Host $rdpRegistry.fDenyTSConnections

    Write-Host ""

    if ($rdpRegistry.fDenyTSConnections -eq 0) {

        Write-Host "RDP STATUS: ENABLED"

    }
    else {

        Write-Host "RDP STATUS: DISABLED"

    }

}

Write-Host ""



# ============================================================
# 7. TCP 3389
# ============================================================

Write-Host "=================================================="
Write-Host "7. TCP PORT 3389"
Write-Host "=================================================="

Write-Host ""

$rdpPort = Get-NetTCPConnection `
    -LocalPort 3389 `
    -ErrorAction SilentlyContinue

if ($null -eq $rdpPort) {

    Write-Host "Port 3389: NOT LISTENING"

}
else {

    Write-Host "Port 3389: LISTENING"

    Write-Host ""

    $rdpPort |
        Select-Object `
            LocalAddress,
            LocalPort,
            State,
            OwningProcess |
        Format-Table -AutoSize

}

Write-Host ""



# ============================================================
# 8. TCP 3389 PROCESS
# ============================================================

Write-Host "=================================================="
Write-Host "8. PORT 3389 PROCESS"
Write-Host "=================================================="

Write-Host ""

if ($null -eq $rdpPort) {

    Write-Host "No process is currently listening on TCP 3389."

}
else {

    foreach ($connection in $rdpPort) {

        try {

            $process = Get-Process `
                -Id $connection.OwningProcess `
                -ErrorAction Stop

            Write-Host "PID:"
            Write-Host $connection.OwningProcess

            Write-Host ""

            Write-Host "Process:"
            Write-Host $process.ProcessName

            Write-Host ""

        }
        catch {

            Write-Host "Unable to identify process."

        }

    }

}

Write-Host ""



# ============================================================
# 9. RDP FIREWALL
# ============================================================

Write-Host "=================================================="
Write-Host "9. REMOTE DESKTOP FIREWALL"
Write-Host "=================================================="

Write-Host ""

try {

    $firewallRules = Get-NetFirewallRule `
        -DisplayGroup "Remote Desktop" `
        -ErrorAction Stop |
        Select-Object `
            DisplayName,
            Enabled,
            Direction,
            Action

    if ($null -eq $firewallRules) {

        Write-Host "No Remote Desktop firewall rules found."

    }
    else {

        $firewallRules |
            Format-Table -AutoSize

    }

}
catch {

    Write-Host "Unable to read Remote Desktop firewall rules."

}

Write-Host ""



# ============================================================
# 10. CURRENT RDP SESSIONS
# ============================================================

Write-Host "=================================================="
Write-Host "10. CURRENT RDP SESSIONS"
Write-Host "=================================================="

Write-Host ""

try {

    $quserResult = quser 2>$null

    if ($quserResult) {

        $quserResult

    }
    else {

        Write-Host "No active RDP/user session information."

    }

}
catch {

    Write-Host "Unable to query user sessions."

}

Write-Host ""



# ============================================================
# 11. WINDOWS FIREWALL STATUS
# ============================================================

Write-Host "=================================================="
Write-Host "11. WINDOWS FIREWALL"
Write-Host "=================================================="

Write-Host ""

try {

    Get-NetFirewallProfile |
        Select-Object `
            Name,
            Enabled,
            DefaultInboundAction,
            DefaultOutboundAction |
        Format-Table -AutoSize

}
catch {

    Write-Host "Unable to read firewall status."

}

Write-Host ""



# ============================================================
# 12. WINDOWS DEFENDER STATUS
# ============================================================

Write-Host "=================================================="
Write-Host "12. WINDOWS DEFENDER"
Write-Host "=================================================="

Write-Host ""

try {

    Get-MpComputerStatus |
        Select-Object `
            AMServiceEnabled,
            AntivirusEnabled,
            AntispywareEnabled,
            RealTimeProtectionEnabled,
            BehaviorMonitorEnabled,
            IoavProtectionEnabled,
            NISEnabled |
        Format-List

}
catch {

    Write-Host "Unable to read Windows Defender status."
    Write-Host "Administrator privileges may be required."

}

Write-Host ""



# ============================================================
# 13. FAILED WINDOWS LOGONS
# ============================================================

Write-Host "=================================================="
Write-Host "13. RECENT FAILED WINDOWS LOGONS"
Write-Host "=================================================="

Write-Host ""

try {

    $failedLogons = Get-WinEvent `
        -FilterHashtable @{
            LogName = 'Security'
            Id = 4625
        } `
        -MaxEvents 10 `
        -ErrorAction Stop

    if ($failedLogons) {

        $failedLogons |
            Select-Object `
                TimeCreated,
                Id,
                ProviderName |
            Format-Table -AutoSize

    }
    else {

        Write-Host "No recent failed logon events found."

    }

}
catch {

    Write-Host "Unable to read Security event log."
    Write-Host "Administrator privileges may be required."

}

Write-Host ""



# ============================================================
# 14. SUCCESSFUL WINDOWS LOGONS
# ============================================================

Write-Host "=================================================="
Write-Host "14. RECENT SUCCESSFUL WINDOWS LOGONS"
Write-Host "=================================================="

Write-Host ""

try {

    $successfulLogons = Get-WinEvent `
        -FilterHashtable @{
            LogName = 'Security'
            Id = 4624
        } `
        -MaxEvents 10 `
        -ErrorAction Stop

    if ($successfulLogons) {

        $successfulLogons |
            Select-Object `
                TimeCreated,
                Id,
                ProviderName |
            Format-Table -AutoSize

    }
    else {

        Write-Host "No recent successful logon events found."

    }

}
catch {

    Write-Host "Unable to read Security event log."
    Write-Host "Administrator privileges may be required."

}

Write-Host ""



# ============================================================
# 15. RDP SPECIFIC FAILED LOGONS
# ============================================================

Write-Host "=================================================="
Write-Host "15. RECENT RDP FAILED LOGONS"
Write-Host "=================================================="

Write-Host ""

try {

    $events = Get-WinEvent `
        -FilterHashtable @{
            LogName = 'Security'
            Id = 4625
        } `
        -MaxEvents 50 `
        -ErrorAction Stop

    $rdpEvents = @()

    foreach ($event in $events) {

        try {

            [xml]$xml = $event.ToXml()

            $data = @{}

            foreach ($item in $xml.Event.EventData.Data) {

                $data[$item.Name] = $item.'#text'

            }

            if ($data['LogonType'] -eq '10') {

                $rdpEvents += [PSCustomObject]@{

                    Time        = $event.TimeCreated
                    User        = $data['TargetUserName']
                    Domain      = $data['TargetDomainName']
                    SourceIP    = $data['IpAddress']
                    LogonType   = $data['LogonType']
                    Status      = $data['Status']
                    SubStatus   = $data['SubStatus']

                }

            }

        }
        catch {

        }

    }

    if ($rdpEvents.Count -eq 0) {

        Write-Host "No recent RDP failed logons found."

    }
    else {

        $rdpEvents |
            Format-Table -AutoSize

    }

}
catch {

    Write-Host "Unable to read RDP Security events."
    Write-Host "Administrator privileges may be required."

}

Write-Host ""



# ============================================================
# 16. REMOTE DESKTOP REGISTRY SETTINGS
# ============================================================

Write-Host "=================================================="
Write-Host "16. RDP CONFIGURATION"
Write-Host "=================================================="

Write-Host ""

try {

    $rdpPath = "HKLM:\SYSTEM\CurrentControlSet\Control\Terminal Server"

    $rdpConfig = Get-ItemProperty `
        -Path $rdpPath `
        -ErrorAction Stop

    Write-Host "fDenyTSConnections:"
    Write-Host $rdpConfig.fDenyTSConnections

    Write-Host ""

    if ($rdpConfig.PSObject.Properties.Name -contains "UserAuthentication") {

        Write-Host "UserAuthentication:"
        Write-Host $rdpConfig.UserAuthentication

    }
    else {

        Write-Host "UserAuthentication: Not available"

    }

}
catch {

    Write-Host "Unable to read RDP configuration."

}

Write-Host ""



# ============================================================
# FINISHED
# ============================================================

Write-Host "=================================================="
Write-Host "       SECURITY DIAGNOSTIC FINISHED"
Write-Host "=================================================="

Write-Host ""

Write-Host "Test Time:"
Write-Host (Get-Date)

Write-Host ""

PS;

        break;


    /*
     * ========================================================
     * UNKNOWN ACTION
     * ========================================================
     */
    default:

        http_response_code(404);

        echo <<<'PS'
Write-Host "Unknown action."
Write-Host ""
Write-Host "Available actions:"
Write-Host "test"
Write-Host "user"
Write-Host "system"
Write-Host "security"
PS;

        break;
}
