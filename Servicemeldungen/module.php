<?php

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class Servicemeldungen extends IPSModule
{
    // Helper
    use ConfigurationForm;
    use Control;
    use Notifications;

    // Constants
    private const LIBRARY_GUID = '{E095D925-0603-3299-3534-EF11FC14E13E}';
    private const MODULE_GUID = '{F93F16FE-A38B-0F42-C7FD-821140955D7D}';
    private const MODULE_PREFIX = 'SM';
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

        // System variables
        $this->RegisterPropertyInteger('NumberOfMessages', 0);
        $this->RegisterPropertyInteger('ServiceTexts', 0);
        $this->RegisterPropertyString('ServiceTextsDelimiter', '***');
        $this->RegisterPropertyString('ServiceTextDelimiter', '|');
        $this->RegisterPropertyBoolean('LogServiceMessages', false);

        // Status values
        $this->RegisterPropertyString('StatusServiceMessages', 'Servicemeldung');
        $this->RegisterPropertyString('StatusNoServiceMessages', 'OK');

        // Notifications
        $this->RegisterPropertyString('Notification', '[]');
        $this->RegisterPropertyString('PushNotification', '[]');
        $this->RegisterPropertyString('PostNotification', '[]');
        $this->RegisterPropertyString('MailerNotification', '[]');

        // Visualization
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableStatus', true);
        $this->RegisterPropertyBoolean('EnableNumberOfMessages', true);
        $this->RegisterPropertyBoolean('EnableMessageList', true);
        $this->RegisterPropertyBoolean('EnableLastUpdate', true);
        $this->RegisterPropertyBoolean('EnableUpdateStatus', true);

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
        IPS_SetVariableProfileAssociation($profile, 0, 'OK', 'shield-check', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, 'Servicemeldung', 'shield-exclamation', 0xFF0000);
        $this->RegisterVariableBoolean('Status', 'Status', $profile, 20);

        // Last update
        $id = @$this->GetIDForIdent('LastUpdate');
        $this->RegisterVariableString('LastUpdate', 'Letzte Aktualisierung', '', 30);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('LastUpdate'), 'Clock');
        }

        // Update status
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.UpdateStatus';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Aktualisieren', 'arrows-rotate', -1);
        $this->RegisterVariableInteger('UpdateStatus', 'Aktualisierung', $profile, 40);
        $this->EnableAction('UpdateStatus');

        // Number of messages
        $id = @$this->GetIDForIdent('NumberOfMessages');
        $this->RegisterVariableInteger('NumberOfMessages', 'Servicemeldungsanzahl', '', 50);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('NumberOfMessages'), 'envelope');
        }

        // Message list
        $id = @$this->GetIDForIdent('MessageList');
        $this->RegisterVariableString('MessageList', 'Servicemeldungen', 'HTMLBox', 60);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('MessageList'), 'rectangle-list');
        }

        ##### Attributes

        $this->RegisterAttributeString('ServiceMessages', '[]');
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
        $propertyNames = ['NumberOfMessages', 'ServiceTexts'];
        foreach ($propertyNames as $propertyName) {
            $id = $this->ReadPropertyInteger($propertyName);
            if (@IPS_VariableExists($id)) {
                $this->RegisterReference($id);
                $this->RegisterMessage($id, VM_UPDATE);
            }
        }

        // Visualization options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('Status'), !$this->ReadPropertyBoolean('EnableStatus'));
        IPS_SetHidden($this->GetIDForIdent('LastUpdate'), !$this->ReadPropertyBoolean('EnableLastUpdate'));
        IPS_SetHidden($this->GetIDForIdent('UpdateStatus'), !$this->ReadPropertyBoolean('EnableUpdateStatus'));
        IPS_SetHidden($this->GetIDForIdent('NumberOfMessages'), !$this->ReadPropertyBoolean('EnableNumberOfMessages'));
        IPS_SetHidden($this->GetIDForIdent('MessageList'), !$this->ReadPropertyBoolean('EnableMessageList'));

        // Update
        $this->UpdateData();
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();

        // Delete profiles
        $profiles = ['Status', 'UpdateStatus'];
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

            case VM_UPDATE:

                // $Data[0] = actual value
                // $Data[1] = value changed
                // $Data[2] = last value
                // $Data[3] = timestamp actual value
                // $Data[4] = timestamp value changed
                // $Data[5] = timestamp last value

                $this->UpdateData();
                break;
        }
    }

    public function CreateInstance(string $ModuleName): void
    {
        //Used in configuration form
        $this->SendDebug(__FUNCTION__, 'Modul: ' . $ModuleName, 0);
        switch ($ModuleName) {
            case 'WebFront':
            case 'WebFrontPush':
                $guid = self::WEBFRONT_MODULE_GUID;
                $name = 'WebFront';
                break;

            case 'TileVisualisation':
                $guid = self::TILE_VISUALISATION_MODULE_GUID;
                $name = 'Kachel Visualisierung';
                break;

            case 'Mailer':
                $guid = self::MAILER_MODULE_GUID;
                $name = 'Mailer';
                break;

            default:
                return;
        }
        $this->SendDebug(__FUNCTION__, 'Guid: ' . $guid, 0);
        $id = @IPS_CreateInstance($guid);
        if (is_int($id)) {
            IPS_SetName($id, $name);
            $infoText = 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            $infoText = 'Instanz konnte nicht erstellt werden!';
        }
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
    }

    ##### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
                $this->SwitchActive($Value);
                break;

            case 'UpdateStatus':
                $this->UpdateData();
                break;

        }
    }

    ##### Protected

    protected function LockSemaphore($Name): bool
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

    protected function UnlockSemaphore($Name): void
    {
        @IPS_SemaphoreLeave(__CLASS__ . '.' . $this->InstanceID . '.' . $Name);
        $this->SendDebug(__FUNCTION__, 'Semaphore ' . $Name . ' unlocked', 0);
    }

    ##### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }
}
