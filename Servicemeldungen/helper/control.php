<?php

declare(strict_types=1);

trait Control
{
    ##### Public

    public function SwitchActive(bool $State): void
    {
        $this->SetValue('Active', $State);
        $this->UpdateFormField('ActiveSwitch', 'value', $State);
    }

    public function ShowServiceMessages(): void
    {
        $messages = $this->GetServiceMessages();
        foreach ($messages as $message) {
            $serviceMessageList[] = ['Status' => '🟡', 'Name' => $message['name'], 'SerialNumber' => $message['serialNumber'], 'ServiceMessage' => $message['message'], 'DateTime' => $message['datetime']];
        }
        $amount = count($messages);
        if ($amount == 0) {
            $serviceMessageList[] = ['Status' => '🟢', 'Name' => '-', 'SerialNumber' => '-', 'ServiceMessage' => 'Keine Servicemeldungen vorhanden', 'DateTime' =>  $this->GetValue('LastUpdate')];
            $amount = 1;
        }
        $this->UpdateFormField('ServiceMessagesList', 'rowCount', $amount);
        $this->UpdateFormField('ServiceMessagesList', 'values', json_encode($serviceMessageList));
    }

    public function UpdateData(): void
    {
        // Enter semaphore first
        if (!$this->LockSemaphore('UpdateData')) {
            $this->SendDebug(__FUNCTION__, 'Abort, Semaphore reached!', 0);
            $this->UnlockSemaphore('UpdateData');
        }
        $this->SetValue('LastUpdate', date('d.m.Y H:i:s'));
        $this->RefactorServiceTexts();
        $this->LogServiceMessages();
        $this->UpdateNumberOfMessages();
        $this->UpdateStatus();
        $this->UpdateMessageList();
        // Leave semaphore
        $this->UnlockSemaphore('UpdateData');
        // Notify
        $this->Notify();
    }

    ##### Protected

    protected function GetServiceMessages(): array
    {
        return json_decode($this->ReadAttributeString('ServiceMessages'), true);
    }

    ##### Private

    private function UpdateStatus(): void
    {
        $status = false;
        if ($this->GetValue('NumberOfMessages') > 0) {
            $status = true;
        }
        $this->SetValue('Status', $status);
    }

    private function UpdateNumberOfMessages(): void
    {
        $numberOfMessages = $this->ReadPropertyInteger('NumberOfMessages');
        if (@IPS_VariableExists($numberOfMessages)) {
            $this->SetValue('NumberOfMessages', GetValue($numberOfMessages));
        } else {
            $this->SetValue('NumberOfMessages', count(json_decode($this->ReadAttributeString('ServiceMessages'), true)));
        }
    }

    private function UpdateMessageList(): void
    {
        $string = '';
        $messages = json_decode($this->ReadAttributeString('ServiceMessages'), true);
        // Check whether the message list is activated for the UI
        if ($this->ReadPropertyBoolean('EnableMessageList')) {
            $string = "<table style='width: 100%; border-collapse: collapse;'>";
            $string .= '<tr><td><b>Status</b></td><td><b>Name</b></td><td><b>Seriennummer</b></td><td><b>Servicemeldung</b></td><td><b>Datum / Uhrzeit</b></td></tr>';
            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $string .= '<tr><td>' . '🟡' . '</td><td>' . $message['name'] . '</td><td>' . $message['serialNumber'] . '</td><td>' . $message['message'] . '</td><td>' . $message['datetime'] . '</td></tr>';
                }
            } else {
                $string .= '<tr><td>' . '🟢' . '</td><td>-</td><td>-</td><td>Keine Servicemeldungen vorhanden</td><td>' . $this->GetValue('LastUpdate') . '</td></tr>';
            }
            $string .= '</table>';
        }
        $this->SetValue('MessageList', $string);

    }

    private function RefactorServiceTexts(): array
    {
        // Example:
        // "Device name | Serial number | service message | date time***"
        // "DG Büro Terrassentür | A1B2C3D4E5F6G7 | Sabotagealarm | 19.06.2025 21:01*** OG Flur Wohnungstür | Sabotagealarm | 19.06.2025 21:02***"
        // Each service message use "***" to mark the end of the service message
        // Within a service message "|" will separate the device name, the service message and the timestamp

        $result = [];
        $serviceTexts = $this->ReadPropertyInteger('ServiceTexts');
        if (@IPS_VariableExists($serviceTexts)) {
            // Split messages
            $entries = explode($this->ReadPropertyString('ServiceTextsDelimiter'), GetValue($serviceTexts));
            foreach ($entries as $entry) {
                // Remove whitespace
                $entry = trim($entry);
                if ($entry === '') continue;
                // Split within messsage
                $items = explode($this->ReadPropertyString('ServiceTextDelimiter'), $entry);
                $name = isset($items[0]) ? trim($items[0]) : '';
                $serialNumber = isset($items[1]) ? trim($items[1]) : '';
                $message = isset($items[2]) ? trim($items[2]) : '';
                $datetime = isset($items[3]) ? trim($items[3]) : '';
                $result[] = [
                    'name'         => $name,
                    'serialNumber' => $serialNumber,
                    'message'      => $message,
                    'datetime'     => $datetime
                ];
            }
        }
        $this->WriteAttributeString('ServiceMessages', json_encode($result));
        return $result;
    }

    private function LogServiceMessages(): void
    {
        if (!$this->ReadPropertyBoolean('LogServiceMessages')) {
            return;
        }
        $messages = json_decode($this->ReadAttributeString('ServiceMessages'), true);
        foreach ($messages as $message) {
            $this->LogMessage($message['name'] . ', ' . $message['serialNumber'] . ', ' . $message['message'] . ', ' . $message['datetime'], KL_WARNING);
        }
    }
}