<?php

/**
 * @project       Zentralenstatus/Zentralenstatus
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpExpressionResultUnusedInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/ZENS_autoload.php';

class Zentralenstatus extends IPSModule
{
    //Helper
    use ZENS_Config;

    //Constants
    private const LIBRARY_GUID = '{E095D925-0603-3299-3534-EF11FC14E13E}';
    private const MODULE_GUID = '{2ED87E59-10F7-2B7E-827E-70BB637E2856}';
    private const MODULE_PREFIX = 'ZENS';
    private const HOMEMATIC_SOCKET_GUID = '{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}';
    private const HOMEMATIC_DEVICE_GUID = '{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Info
        $this->RegisterPropertyString('Note', '');
        //Functions
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableStatus', true);
        $this->RegisterPropertyBoolean('EnableAmountDeviceStatusUpdates', true);
        $this->RegisterPropertyBoolean('EnableResetDeviceStatusUpdates', true);

        //Homematic socket
        $this->RegisterPropertyInteger('HomematicSocket', 0);
        $this->RegisterPropertyInteger('ReOpenTime', 60);
        $this->RegisterPropertyBoolean('UseDeviceStatusUpdates', true);
        $this->RegisterPropertyInteger('MaximumAmountDeviceStatusUpdates', 4);
        $this->RegisterPropertyString('Actions', '[]');

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        //Status
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Status';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'OK', 'Network', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, 'Fehler', 'Warning', 0xFF0000);
        $this->RegisterVariableBoolean('Status', 'Verbindungsstatus', $profile, 20);

        //Amount device status updates
        $id = @$this->GetIDForIdent('AmountDeviceStatusUpdates');
        $this->RegisterVariableInteger('AmountDeviceStatusUpdates', 'Gerätestatusaktualisierungen', '', 30);
        if (!$id) {
            IPS_SetIcon(@$this->GetIDForIdent('AmountDeviceStatusUpdates'), 'Ok');
        }

        //Reset device status updates
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.ResetDeviceStatusUpdates';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Reset', 'Repeat', 0xFF0000);
        $this->RegisterVariableInteger('ResetDeviceStatusUpdates', 'Rückstellung', $profile, 40);
        $this->EnableAction('ResetDeviceStatusUpdates');

        ########## Timer

        $this->RegisterTimer('ReOpenSocket', 0, self::MODULE_PREFIX . '_ReOpenSocket(' . $this->InstanceID . ');');
        $this->RegisterTimer('ResetDeviceStatusUpdates', 0, self::MODULE_PREFIX . '_ResetDeviceStatusUpdates(' . $this->InstanceID . ');');
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all update messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        //Register references and messages
        //Homematic socket
        $id = $this->ReadPropertyInteger('HomematicSocket');
        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
            $this->RegisterReference($id);
            $this->RegisterMessage($id, IM_CHANGESTATUS);
        }

        //Actions
        $items = json_decode($this->ReadPropertyString('Actions'), true);
        foreach ($items as $item) {
            if (!$item['Use']) {
                continue;
            }
            if ($item['Action'] != '') {
                $action = json_decode($item['Action'], true);
                if (array_key_exists('parameters', $action)) {
                    if (array_key_exists('TARGET', $action['parameters'])) {
                        $id = $action['parameters']['TARGET'];
                        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                            $this->RegisterReference($id);
                        }
                    }
                }
            }
        }

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('Status'), !$this->ReadPropertyBoolean('EnableStatus'));
        IPS_SetHidden($this->GetIDForIdent('AmountDeviceStatusUpdates'), !$this->ReadPropertyBoolean('EnableAmountDeviceStatusUpdates'));
        IPS_SetHidden($this->GetIDForIdent('ResetDeviceStatusUpdates'), !$this->ReadPropertyBoolean('EnableResetDeviceStatusUpdates'));

        //Timer
        $this->SetTimerInterval('ReOpenSocket', 0);
        $this->SetTimerInterval('ResetDeviceStatusUpdates', (strtotime('next day midnight') - time()) * 1000);

        $this->CheckStatus();
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['Status', 'ResetDeviceStatusUpdates'];
        foreach ($profiles as $profile) {
            $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case IM_CHANGESTATUS:
                //$Data[0] = actual value
                //$Data[1] = previous value
                $this->CheckStatus();
                break;

        }
    }

    #################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
                $this->SetValue($Ident, $Value);
                $this->CheckStatus();
                break;

            case 'ResetDeviceStatusUpdates':
                $this->ResetDeviceStatusUpdates();
                break;

        }
    }

    #################### Public

    /**
     * Checks the actual status of the Homematic socket.
     *
     * @return void
     * @throws Exception
     */
    public function CheckStatus(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if (!$this->GetValue('Active')) {
            $this->SetTimerInterval('ReOpenSocket', 0);
            return;
        }
        $instanceStatus = $this->GetInstanceStatus();
        if ($instanceStatus == 0) {
            $this->SetTimerInterval('ReOpenSocket', 0);
            return;
        }
        $reconnect = false;
        if ($instanceStatus == 102) {
            $this->SendDebug(__FUNCTION__, 'Die Verbindung zur Zentrale ist hergestellt.', 0);
            $this->LogMessage('Die Verbindung zur Zentrale ist hergestellt.', KL_NOTIFY);
            $this->SetValue('Status', 0);
            $this->SetTimerInterval('ReOpenSocket', 0);
            //Update Homematic device states
            if ($this->ReadPropertyBoolean('UseDeviceStatusUpdates')) {
                //Check if the host is alive
                $host = json_decode(IPS_GetConfiguration($this->ReadPropertyInteger('HomematicSocket')), true)['Host'];
                if (Sys_Ping($host, 1000)) {
                    $amountDeviceStatusUpdates = $this->GetValue('AmountDeviceStatusUpdates');
                    $amountDeviceStatusUpdates++;
                    if ($amountDeviceStatusUpdates <= $this->ReadPropertyInteger('MaximumAmountDeviceStatusUpdates')) {
                        //Enter semaphore
                        if (!$this->LockSemaphore('DeviceStatusUpdates')) {
                            $this->SendDebug(__FUNCTION__, 'Abbruch, das Semaphore wurde erreicht!', 0);
                            $this->UnlockSemaphore('DeviceStatusUpdates');
                            $this->SetTimerInterval('ReOpenSocket', $this->ReadPropertyInteger('ReOpenTime') * 1000);
                            return;
                        }
                        $devices = IPS_GetInstanceListByModuleID(self::HOMEMATIC_DEVICE_GUID);
                        foreach ($devices as $device) {
                            $children = IPS_GetChildrenIDs($device);
                            foreach ($children as $child) {
                                $object = IPS_GetObject($child);
                                if ($object['ObjectIdent'] == 'STATE' || $object['ObjectIdent'] == 'MOTION') {
                                    @HM_RequestStatus($device, $object['ObjectIdent']);
                                }
                            }
                        }
                        $this->LogMessage('Die ' . $amountDeviceStatusUpdates . '. Gerätestatusaktualisierung wurde durchgeführt!', KL_NOTIFY);
                        $this->SetValue('AmountDeviceStatusUpdates', $amountDeviceStatusUpdates);
                        //Actions
                        $actions = json_decode($this->ReadPropertyString('Actions'), true);
                        foreach ($actions as $action) {
                            $action = json_decode($action['Action'], true);
                            @IPS_RunAction($action['actionID'], $action['parameters']);
                        }
                        //Leave semaphore
                        $this->UnlockSemaphore('DeviceStatusUpdates');
                    }
                } //No response from host
                else {
                    $reconnect = true;
                }
            }
        } //Status is not 102
        else {
            $reconnect = true;
        }
        if ($reconnect) {
            $this->SendDebug(__FUNCTION__, 'Es besteht keine Verbindung zur Zentrale!', 0);
            $this->LogMessage('Es besteht keine Verbindung zur Zentrale!', KL_ERROR);
            $this->SetTimerInterval('ReOpenSocket', $this->ReadPropertyInteger('ReOpenTime') * 1000);
        }
    }

    /**
     * Reopen the Homematic socket.
     *
     * @return void
     * @throws Exception
     */
    public function ReOpenSocket(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if (!$this->GetValue('Active')) {
            $this->SetTimerInterval('ReOpenSocket', 0);
            return;
        }
        $id = $this->ReadPropertyInteger('HomematicSocket');
        if ($id <= 1 || @!IPS_ObjectExists($id)) {
            $this->SetTimerInterval('ReOpenSocket', 0);
            return;
        }
        $instanceStatus = IPS_GetInstance($id)['InstanceStatus'];
        //Socket is already reconnected
        if ($instanceStatus == 102) {
            $this->SetTimerInterval('ReOpenSocket', 0);
        }
        //Try to reopen socket
        if ($instanceStatus != 102) {
            $host = json_decode(IPS_GetConfiguration($id), true)['Host'];
            if (Sys_Ping($host, 1000)) {
                //Enter semaphore
                if (!$this->LockSemaphore('ReOpenSocket')) {
                    $this->SendDebug(__FUNCTION__, 'Abbruch, das Semaphore wurde erreicht!', 0);
                    $this->UnlockSemaphore('ReOpenSocket');
                    $this->SetTimerInterval('ReOpenSocket', $this->ReadPropertyInteger('ReOpenTime') * 1000);
                    return;
                }
                @IPS_SetProperty($id, 'Open', true);
                if (@IPS_HasChanges($id)) {
                    @IPS_ApplyChanges($id);
                }
                //Leave semaphore
                $this->UnlockSemaphore('ReOpenSocket');
            }
            $this->SetTimerInterval('ReOpenSocket', $this->ReadPropertyInteger('ReOpenTime') * 1000);
        }
    }

    /**
     * Resets the amount of the device state updates at midnight.
     *
     * @return void
     */
    public function ResetDeviceStatusUpdates(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SetValue('AmountDeviceStatusUpdates', 0);
        $this->SetTimerInterval('ResetDeviceStatusUpdates', (strtotime('next day midnight') - time()) * 1000);
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    /**
     * Gets the actual status of the instance.
     *
     * @return int
     * @throws Exception
     */
    private function GetInstanceStatus(): int
    {
        $id = $this->ReadPropertyInteger('HomematicSocket');
        if ($id > 1 || @!IPS_ObjectExists($id)) { //0 = main category, 1 = none
            return @IPS_GetInstance($id)['InstanceStatus'];
        }
        return 0;
    }

    /**
     * Locks the semaphore.
     *
     * @param $Name
     * Name of the semaphore
     *
     * @return bool
     * false =  failed
     * true =   ok
     */
    private function LockSemaphore($Name): bool
    {
        for ($i = 0; $i < 1000; $i++) {
            if (IPS_SemaphoreEnter(__CLASS__ . '.' . $this->InstanceID . '.' . $Name, 1)) {
                $this->SendDebug(__FUNCTION__, 'Semaphore ' . $Name . ' locked', 0);
                return true;
            } else {
                IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    /**
     * Unlocks the semaphore.
     *
     * @param $Name
     * Name of the semaphore
     *
     * @return void
     */
    private function UnlockSemaphore($Name): void
    {
        @IPS_SemaphoreLeave(__CLASS__ . '.' . $this->InstanceID . '.' . $Name);
        $this->SendDebug(__FUNCTION__, 'Semaphore ' . $Name . ' unlocked', 0);
    }
}
