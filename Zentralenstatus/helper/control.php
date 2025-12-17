<?php

declare(strict_types=1);

trait control
{
    public function UpdateDeviceStatus(): void
    {
        // Do some checks first
        if ($this->CheckMaintenance()) {
            return;
        }
        if (!$this->CheckSocketInstance()) {
            return;
        }
        $socketID = $this->ReadPropertyInteger('HomematicSocket');
        if ($this->GetSocketStatus($socketID) != 102) {
            return;
        }

        // Double-check if the host is alive
        $host = json_decode(IPS_GetConfiguration($socketID), true)['Host'];
        if (Sys_Ping($host, 1000)) {
            // Enter semaphore
            if (!$this->LockSemaphore('UpdateDeviceStatus')) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, das Semaphore wurde erreicht!', 0);
                $this->UnlockSemaphore('UpdateDeviceStatus');
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
            $this->LogMessage('Die Gerätestatusaktualisierung wurde durchgeführt!', KL_NOTIFY);
            $this->UpdateFormField('InfoMessageLabel', 'caption', '✅ Die Gerätestatusaktualisierung wurde erfolgreich durchgeführt!');
            $this->UpdateFormField('InfoMessage', 'visible', true);

            // Actions
            $actions = json_decode($this->ReadPropertyString('Actions'), true);
            foreach ($actions as $action) {
                $action = json_decode($action['Action'], true);
                @IPS_RunAction($action['actionID'], $action['parameters']);
            }

            // Leave semaphore
            $this->UnlockSemaphore('UpdateDeviceStatus');
        } else {
            $this->UpdateFormField('InfoMessageLabel', 'caption', '⚠️ Die Gerätestatusaktualisierung konnte nicht durchgeführt werden!');
            $this->UpdateFormField('InfoMessage', 'visible', true);
        }
    }

    public function CheckSocket(): void
    {
        // Do some checks first
        if ($this->CheckMaintenance()) {
            return;
        }
        if (!$this->CheckSocketInstance()) {
            return;
        }
        $id = $this->ReadPropertyInteger('HomematicSocket');
        if ($this->GetSocketStatus($id) == 102) {
            $this->StopReOpenSocketTimer();
            return;
        }

        // Try to reopen the socket
        $host = json_decode(IPS_GetConfiguration($id), true)['Host'];
        if (Sys_Ping($host, 1000)) {
            // Enter semaphore
            if (!$this->LockSemaphore('CheckSocket')) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, das Semaphore wurde erreicht!', 0);
                $this->UnlockSemaphore('CheckSocket');
                // Try again later
                $this->StartReOpenSocketTimer();
                return;
            }

            // Device is ready to be connected
            @IPS_SetProperty($id, 'Open', true);
            if (@IPS_HasChanges($id)) {
                @IPS_ApplyChanges($id);
            }

            // Leave semaphore
            $this->UnlockSemaphore('CheckSocket');
        } else {
            // Try again later
            $this->StartReOpenSocketTimer();

        }

        if ($this->ReadAttributeBoolean('InitialNotification')) {
            $this->WriteAttributeBoolean('InitialNotification', false);
            $socketStatus = $this->GetSocketStatus($id);
            if ($socketStatus != 102) {
                $this->Notify(false);
            }
        }
    }

    ##### Protected

    /**
     * Checks if the maintenance mode is active.
     *
     * @return bool
     * false =  no maintenance mode active
     * true =   maintenance mode active
     * @throws Exception
     */
    protected function CheckMaintenance(): bool
    {
        if ($this->GetValue('Active')) {
            return false;
        }
        $this->StopReOpenSocketTimer();
        return true;
    }

    protected function CheckSocketInstance(): bool
    {
        if (!$this->CheckObjectID($this->ReadPropertyInteger('HomematicSocket'))) {
            $this->StopReOpenSocketTimer();
            return false;
        }
        return true;
    }

    protected function CheckObjectID(int $ObjectID): bool
    {
        if ($ObjectID < 10000 || @!IPS_ObjectExists($ObjectID)) {
            return false;
        }
        return true;
    }
    protected function StopReOpenSocketTimer(): void
    {
        $this->SetTimerInterval('ReOpenSocket', 0);
    }

    protected function StartReOpenSocketTimer(): void
    {
        $this->SetTimerInterval('ReOpenSocket', $this->ReadPropertyInteger('ReconnectionTime') * 1000);
    }

    ##### Private

    protected function GetSocketStatus($SocketID): int
    {
        return @IPS_GetInstance($SocketID)['InstanceStatus'];
    }

    ##### Semaphores

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

    private function UnlockSemaphore($Name): void
    {
        @IPS_SemaphoreLeave(__CLASS__ . '.' . $this->InstanceID . '.' . $Name);
        $this->SendDebug(__FUNCTION__, 'Semaphore ' . $Name . ' unlocked', 0);
    }
}