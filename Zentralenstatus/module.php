<?php

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class Zentralenstatus extends IPSModule
{
    // Helper
    use configurationForm;
    use control;
    use notifications;

    // Constants
    private const LIBRARY_GUID = '{E095D925-0603-3299-3534-EF11FC14E13E}';
    private const MODULE_GUID = '{2ED87E59-10F7-2B7E-827E-70BB637E2856}';
    private const MODULE_PREFIX = 'ZENS';
    private const HOMEMATIC_SOCKET_GUID = '{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}';
    private const HOMEMATIC_DEVICE_GUID = '{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}';
    private const NOTIFICATION_MODULE_GUID = '{BDAB70AA-B45D-4CB4-3D65-509CFF0969F9}';
    private const WEBFRONT_MODULE_GUID = '{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}';
    private const TILE_VISUALISATION_MODULE_GUID = '{B5B875BB-9B76-45FD-4E67-2607E45B3AC4}';
    private const MAILER_MODULE_GUID = '{C6CF3C5C-E97B-97AB-ADA2-E834976C6A92}';

    public function Create()
    {
        // Never delete this line!
        parent::Create();

        ##### Properties

        // Info
        $this->RegisterPropertyString('Note', '');

        // Homematic socket
        $this->RegisterPropertyInteger('HomematicSocket', 0);
        $this->RegisterPropertyInteger('ReconnectionTime', 60);
        $this->RegisterPropertyBoolean('UseMessageLog', true);

        // Actions
        $this->RegisterPropertyString('Actions', '[]');

        // Notifications
        $this->RegisterPropertyString('Notification', '[]');
        $this->RegisterPropertyString('PushNotification', '[]');
        $this->RegisterPropertyString('PostNotification', '[]');
        $this->RegisterPropertyString('MailerNotification', '[]');

        // Visualization
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableStatus', true);
        $this->RegisterPropertyBoolean('EnableDeviceStatusUpdate', true);

        ##### Variables

        // Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        // Status
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Status';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'OK', 'Network', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, 'Fehler', 'Warning', 0xFF0000);
        $this->RegisterVariableBoolean('Status', 'Verbindungsstatus', $profile, 20);

        // Device status update
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DeviceStatusUpdate';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Aktualisieren', 'Repeat', 0xFF0000);
        $this->RegisterVariableInteger('DeviceStatusUpdate', 'Gerätestatus', $profile, 40);
        $this->EnableAction('DeviceStatusUpdate');

        ##### Timer

        $this->RegisterTimer('ReOpenSocket', 0, self::MODULE_PREFIX . '_CheckSocket(' . $this->InstanceID . ');');

        ##### Attribute

        $this->RegisterAttributeBoolean('InitialNotification', true);
    }

    public function ApplyChanges()
    {
        // Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        // Never delete this line!
        parent::ApplyChanges();

        // Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        // Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        // Delete all update messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        // Register references and messages
        // Homematic socket
        $id = $this->ReadPropertyInteger('HomematicSocket');
        if ($this->CheckObjectID($id)) {
            $this->RegisterReference($id);
            $this->RegisterMessage($id, IM_CHANGESTATUS);
        }

        // Actions
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
                        if ($this->CheckObjectID($id)) {
                            $this->RegisterReference($id);
                        }
                    }
                }
            }
        }

        // Notifications
        $notificationTypes = ['Notification', 'PushNotification', 'PostNotification', 'MailerNotification'];
        foreach ($notificationTypes as $notificationType) {
            foreach (json_decode($this->ReadPropertyString($notificationType), true) as $notification) {
                // Checks
                if (!$notification['Use']) {
                    continue;
                }
                $id = $notification['ID'];
                if ($this->CheckObjectID($id)) {
                    $this->RegisterReference($id);
                }
            }
        }

        // Visualization options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('Status'), !$this->ReadPropertyBoolean('EnableStatus'));
        IPS_SetHidden($this->GetIDForIdent('DeviceStatusUpdate'), !$this->ReadPropertyBoolean('EnableDeviceStatusUpdate'));

        // Timer
        $this->SetTimerInterval('ReOpenSocket', 0);

        $this->WriteAttributeBoolean('InitialNotification', true);
        $this->CheckSocket();
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();

        // Delete profiles
        $profiles = ['Status', 'DeviceStatusUpdate'];
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

                // $Data[0] = actual value
                // $Data[1] = previous value

                if ($this->ReadPropertyBoolean('UseMessageLog')) {
                    $this->LogMessage('Status von ' . $SenderID . ' hat sich geändert um ' . date('H:m:s U') . ', vorheriger Wert: ' . $Data[1] . ', aktueller Wert: ' . $Data[0], KL_NOTIFY);
                }

                $this->UpdateSocketForm($this->ReadPropertyInteger('HomematicSocket'));

                if ($Data[0] != $Data[1]) {
                    $state = true;
                    if ($Data[0] != 102) {
                        $state = false;
                        $this->StartReOpenSocketTimer();
                    }
                    $this->Notify($state);
                }

                if ($Data[0] == 102) {
                    $this->StopReOpenSocketTimer();
                }
                break;

        }
    }

    ##### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
                $this->SetValue($Ident, $Value);
                if (!$Value) {
                    $this->StopReOpenSocketTimer();
                } else {
                    $this->WriteAttributeBoolean('InitialNotification', true);
                    $this->CheckSocket();
                }
                break;

            case 'DeviceStatusUpdate':
                $this->UpdateDeviceStatus();
                break;

        }
    }

    ##### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }
}
