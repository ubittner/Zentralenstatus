<?php

declare(strict_types=1);

trait Notifications
{
    public function Notify(): void
    {
        if (!$this->GetValue('Active')) {
            return;
        }
        $notificationTypes = ['Notification', 'PushNotification', 'PostNotification', 'MailerNotification'];
        $messages = json_decode($this->ReadAttributeString('ServiceMessages'), true);
        foreach ($notificationTypes as $notificationType) {
            foreach (json_decode($this->ReadPropertyString($notificationType), true) as $key => $notification) {
                if (!$notification['Use']) {
                    continue;
                }
                if ($notification['ID'] < 10000 || @!IPS_ObjectExists($notification['ID'])) {
                    continue;
                }
                foreach ($messages as $message) {
                    if (!$this->isServiceMessageEnabled($notificationType, $key, $message['message'])) {
                        continue;
                    }

                    if ($notificationType != 'MailerNotification') {
                        $title = $notification['Title'];
                    }

                    $text = sprintf(
                        $notification['Text'],
                        $message['name'],
                        $message['serialNumber'],
                        $message['message'],
                        $message['datetime']
                    );

                    // Notification

                    if ($notificationType == 'Notification' && isset($title)) {
                        $this->SendNotification($notification['ID'], $title, $text, $notification['Icon'], $notification['DisplayDuration']);
                    }

                    if ($notificationType == 'PushNotification' || $notificationType == 'PostNotification') {
                        // Title length max 32 characters
                        $title = substr($notification['Title'], 0, 32);
                        // Text length max 256 characters
                        $text = "\n" . $text;
                        $text = substr($text, 0, 256);
                    }

                    // Push notification

                    if ($notificationType == 'PushNotification' && isset($title)) {
                        $this->SendPushNotification($notification['ID'], $title, $text, $notification['Sound'], $notification['TargetID']);
                    }

                    // Post Notification

                    if ($notificationType == 'PostNotification' && isset($title)) {
                        $this->SendPostNotification($notification['ID'], $title, $text, $notification['Icon'], $notification['Sound'], $notification['TargetID']);
                    }

                    // Mailer notification

                    if ($notificationType == 'MailerNotification') {
                        $this->SendMail($notification['ID'], $notification['Subject'], $text);
                    }
                }
            }
        }
    }

    ##### Protected

    protected function isServiceMessageEnabled(string $NotificationType, int $Index, string $MessageText): bool
    {
        $this->SendDebug(__FUNCTION__, 'Servicemeldung: ' . $MessageText, 0);
        $useMessageTypes = ['UseServiceMessageLowBattery', 'UseServiceMessageUnreach', 'UseServiceMessageSabotage', 'UseServiceMessageOther'];
        $notificationType = json_decode($this->ReadPropertyString($NotificationType), true);
        if (empty($notificationType)) {
            return false;
        }
        //Check if all service messages are enabled
        $allEnabled = true;
        foreach ($useMessageTypes as $useMessageType) {
            if (!$notificationType[$Index][$useMessageType]) {
                $allEnabled = false;
            }
        }
        if ($allEnabled) {
            return true;
        }
        // If not all enabled, check defined service messages
        else {
            $messageTypes = [
                'UseServiceMessageLowBattery' => 'Batterieladezustand gering',
                'UseServiceMessageUnreach'    => 'Gerätekommunikation gestört',
                'UseServiceMessageSabotage'   => 'Sabotage',
            ];
            foreach ($messageTypes as $key => $messageType) {
                if ($MessageText == $messageType) {
                    $useMessageType = $key;
                    if ($notificationType[$Index][$useMessageType]) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
            // If it is not a defined service message, check other service messages
            if ($notificationType[$Index]['UseServiceMessageOther']) {
                return true;
            } else {
                return false;
            }
        }
    }

    protected function SendNotification(int $InstanceID, string $Title, string $Text, string $Icon, int $Duration): void
    {
        $scriptText = sprintf(
            'WFC_SendNotification(%d, "%s", "%s", "%s", %d);',
            $InstanceID,
            $Title,
            $Text,
            $Icon,
            $Duration
        );
        @IPS_RunScriptText($scriptText);
    }

    protected function SendPushNotification(int $InstanceID, string $Title, string $Text, string $Sound, int $TargetID): void
    {
        $scriptText = sprintf(
            'WFC_PushNotification(%d, "%s", "%s", "%s", %d);',
            $InstanceID,
            $Title,
            $Text,
            $Sound,
            $TargetID
        );
        @IPS_RunScriptText($scriptText);
    }

    protected function SendPostNotification(int $InstanceID, string $Title, string $Text, string $Icon, string $Sound, int $TargetID): void
    {
        $scriptText = sprintf(
            'VISU_PostNotificationEx(%d, "%s", "%s", "%s", "%s", %d);',
            $InstanceID,
            $Title,
            $Text,
            $Icon,
            $Sound,
            $TargetID
        );
        @IPS_RunScriptText($scriptText);
    }

    protected function SendMail(int $InstanceID, string $Subject, string $Text): void
    {
        $scriptText = sprintf(
            'MA_SendMessage(%d, "%s", "%s");',
            $InstanceID,
            $Subject,
            $Text
        );
        @IPS_RunScriptText($scriptText);
    }
}