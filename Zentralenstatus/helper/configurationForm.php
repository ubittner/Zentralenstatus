<?php

declare(strict_types=1);

trait configurationForm
{
    public function ReloadConfig(): void
    {
        $this->ReloadForm();
    }

    public function ExpandExpansionPanels(bool $State): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->UpdateFormField('Panel' . $i, 'expanded', $State);
        }
    }

    public function ModifyButton(string $Field, string $Caption, int $ObjectID): void
    {
        $state = false;
        if ($this->CheckObjectID($ObjectID)) {
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', $Caption);
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $ObjectID);
    }

    public function UpdateSocketForm(int $SocketID): array
    {
        $this->SendDebug(__FUNCTION__, 'SocketID: ' . $SocketID, 0);
        $visible = false;
        $statusCaption = '🔴';
        $buttonCaption = 'ID ' . $SocketID . ' Instanzkonfiguration';
        if ($this->CheckObjectID($SocketID)) {
            $visible = true;
            if ($this->GetSocketStatus($SocketID) == 102) {
                $statusCaption = '🟢';
            }
        }
        $field = 'SocketStatusLabel';
        $this->UpdateFormField($field, 'caption', $statusCaption);
        $this->UpdateFormField($field, 'visible', $visible);
        $field = 'HomematicSocketConfigurationButton';
        $this->UpdateFormField($field, 'caption', $buttonCaption);
        $this->UpdateFormField($field, 'visible', $visible);
        $this->UpdateFormField($field, 'objectID', $SocketID);
        return ['visible' => $visible, 'statusCaption' => $statusCaption, 'buttonCaption' => $buttonCaption, 'objectID' => $SocketID];
    }

    public function GetConfigurationForm()
    {
        $form = [];

        ##### Elements

        // Configuration buttons
        $form['elements'][0] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration ausklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, true);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration einklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, false);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration neu laden',
                        'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                    ]
                ]
            ];

        // Info
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $module = IPS_GetModule(self::MODULE_GUID);
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel1',
            'caption' => 'Info',
            'items'   => [
                [
                    'type'    => 'Label',
                    'name'    => 'ModuleID',
                    'caption' => "ID:\t\t\t" . $this->InstanceID
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Modul:\t\t" . $module['ModuleName']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Präfix:\t\t" . $module['Prefix']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Version:\t\t" . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date'])
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Entwickler:\t" . $library['Author']
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Note',
                    'caption' => 'Notiz',
                    'width'   => '600px'
                ]
            ]
        ];

        $socketForm = $this->UpdateSocketForm($this->ReadPropertyInteger('HomematicSocket'));

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel2',
            'caption' => 'Homematic Socket',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectModule',
                            'name'     => 'HomematicSocket',
                            'caption'  => 'Homematic Socket',
                            'moduleID' => self::HOMEMATIC_SOCKET_GUID,
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_UpdateSocketForm($id, $HomematicSocket);'
                        ],
                        [
                            'type'     => 'Label',
                            'name'     => 'SocketStatusLabel',
                            'caption'  => $socketForm['statusCaption'],
                            'visible'  => $socketForm['visible']
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'caption'  => $socketForm['buttonCaption'],
                            'name'     => 'HomematicSocketConfigurationButton',
                            'visible'  => $socketForm['visible'],
                            'objectID' => $socketForm['objectID']
                        ]
                    ]
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'ReconnectionTime',
                    'caption' => 'Wiederverbindung nach',
                    'minimum' => 1,
                    'maximum' => 6000,
                    'suffix'  => 'Sekunden'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseMessageLog',
                    'caption' => 'Protokollieren'
                ]
            ]
        ];

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel3',
            'caption' => 'Aktionen',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'Actions',
                    'caption'  => 'Aktionen bei Wiederverbindung',
                    'rowCount' => $this->CountRows('Actions'),
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Bezeichnung',
                            'name'    => 'Designation',
                            'width'   => '400px',
                            'add'     => '',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Aktion',
                            'name'    => 'Action',
                            'width'   => '800px',
                            'add'     => '',
                            'visible' => true,
                            'edit'    => [
                                'type' => 'SelectAction'
                            ]
                        ]
                    ],
                    'values' => $this->GetRowColors('Actions')
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Anzahl Aktionen: ' . $this->CountElements('Actions')
                ]
            ]
        ];

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel4',
            'caption' => 'Benachrichtigungen',
            'items'   => [

                // Notification

                [
                    'type'     => 'List',
                    'name'     => 'Notification',
                    'caption'  => 'Nachricht (WebFront Visualisierung)',
                    'rowCount' => $this->CountRows('Notification'),
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "NotificationConfigurationButton", "ID " . $Notification["ID"] . " konfigurieren", $Notification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'ConnectionLostSpacer',
                            'width'   => '100px',
                            'visible' => false,
                            'save'    => false,
                            'add'     => '',
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Verbindung verloren',
                            'name'    => 'UseConnectionLost',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'ConnectionLostTitle',
                            'width'   => '350px',
                            'add'     => 'Homematic Socket',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'ConnectionLostText',
                            'width'   => '200px',
                            'add'     => '🔴 Verbindung verloren!',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'ConnectionLostIcon',
                            'width'   => '200px',
                            'add'     => 'Warning',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'ConnectionLostDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'ConnectionEstablishedSpacer',
                            'width'   => '100px',
                            'visible' => false,
                            'save'    => false,
                            'add'     => '',
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Verbindung wiederhergestellt',
                            'name'    => 'UseConnectionEstablished',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'ConnectionEstablishedTitle',
                            'width'   => '350px',
                            'add'     => 'Homematic Socket',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'ConnectionEstablishedText',
                            'width'   => '200px',
                            'add'     => '🟢 Verbindung hergestellt!',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'ConnectionEstablishedIcon',
                            'width'   => '200px',
                            'add'     => 'Network',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'ConnectionEstablishedDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ]
                    ],
                    'values' => $this->GetRowColors('Notification'),
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'NotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                // Push notification

                [
                    'type'     => 'List',
                    'name'     => 'PushNotification',
                    'caption'  => 'Push-Nachricht (WebFront Visualisierung)',
                    'rowCount' => $this->CountRows('PushNotification'),
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "PushNotificationConfigurationButton", "ID " . $PushNotification["ID"] . " konfigurieren", $PushNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'ConnectionLostSpacer',
                            'width'   => '100px',
                            'visible' => false,
                            'save'    => false,
                            'add'     => '',
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Verbindung verloren',
                            'name'    => 'UseConnectionLost',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'ConnectionLostTitle',
                            'width'   => '350px',
                            'add'     => 'Homematic Socket',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'ConnectionLostText',
                            'width'   => '200px',
                            'add'     => '🔴 Verbindung verloren!',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'ConnectionLostSound',
                            'width'   => '200px',
                            'add'     => 'alarm',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'ConnectionLostTargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'ConnectionEstablishedSpacer',
                            'width'   => '100px',
                            'visible' => false,
                            'save'    => false,
                            'add'     => '',
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Verbindung wiederhergestellt',
                            'name'    => 'UseConnectionEstablished',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'ConnectionEstablishedTitle',
                            'width'   => '350px',
                            'add'     => 'Homematic Socket',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'ConnectionEstablishedText',
                            'width'   => '200px',
                            'add'     => '🟢 Verbindung hergestellt!',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'ConnectionEstablishedSound',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'ConnectionEstablishedTargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
                            ]
                        ],
                    ],
                    'values' => $this->GetRowColors('PushNotification'),
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'PushNotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                // Post notification

                [
                    'type'     => 'List',
                    'name'     => 'PostNotification',
                    'caption'  => 'Post-Nachricht (Kachel Visualisierung)',
                    'rowCount' => $this->CountRows('PushNotification'),
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Kachel Visualisierung',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "PostNotificationConfigurationButton", "ID " . $PostNotification["ID"] . " konfigurieren", $PostNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::TILE_VISUALISATION_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'ConnectionLostSpacer',
                            'width'   => '100px',
                            'visible' => false,
                            'save'    => false,
                            'add'     => '',
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Verbindung verloren',
                            'name'    => 'UseConnectionLost',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'ConnectionLostTitle',
                            'width'   => '350px',
                            'add'     => 'Homematic Socket',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'ConnectionLostText',
                            'width'   => '200px',
                            'add'     => '🔴 Verbindung verloren!',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'ConnectionLostIcon',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'ConnectionLostSound',
                            'width'   => '200px',
                            'add'     => 'alarm',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Ziel ID',
                            'name'    => 'ConnectionLostTargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'ConnectionEstablishedSpacer',
                            'width'   => '100px',
                            'visible' => false,
                            'save'    => false,
                            'add'     => '',
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Verbindung wiederhergestellt',
                            'name'    => 'UseConnectionEstablished',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'ConnectionEstablishedTitle',
                            'width'   => '350px',
                            'add'     => 'Homematic Socket',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'ConnectionEstablishedText',
                            'width'   => '200px',
                            'add'     => '🟢 Verbindung hergestellt!',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'ConnectionEstablishedIcon',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'ConnectionEstablishedSound',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Ziel ID',
                            'name'    => 'ConnectionEstablishedTargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
                            ]
                        ],
                    ],
                    'values' => $this->GetRowColors('PostNotification'),
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "TileVisualisation");'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'ImmediatePostNotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                // Email notification

                [
                    'type'     => 'List',
                    'name'     => 'MailerNotification',
                    'caption'  => 'E-Mail (Mailer)',
                    'rowCount' => $this->CountRows('MailerNotification'),
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Mailer',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "MailerConfigurationButton", "ID " . $MailerNotification["ID"] . " konfigurieren", $MailerNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::MAILER_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'ConnectionLostSpacer',
                            'width'   => '100px',
                            'visible' => false,
                            'save'    => false,
                            'add'     => '',
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Verbindung verloren',
                            'name'    => 'UseConnectionLost',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Betreff',
                            'name'    => 'ConnectionLostSubject',
                            'width'   => '350px',
                            'add'     => 'Homematic Socket',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'ConnectionLostText',
                            'width'   => '200px',
                            'add'     => '🔴 Verbindung zur Homematic Zentrale verloren!',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'ConnectionEstablishedSpacer',
                            'width'   => '100px',
                            'visible' => false,
                            'save'    => false,
                            'add'     => '',
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Verbindung wiederhergestellt',
                            'name'    => 'UseConnectionEstablished',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Betreff',
                            'name'    => 'ConnectionEstablishedSubject',
                            'width'   => '350px',
                            'add'     => 'Homematic Socket',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'ConnectionEstablishedText',
                            'width'   => '200px',
                            'add'     => '🟢 Verbindung zur Homematic Zentrale hergestellt!',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ]
                    ],
                    'values' => $this->GetRowColors('MailerNotification'),
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "Mailer");'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'MailerConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ]
            ]
        ];

        // Visualisation
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel5',
            'caption' => 'Visualisierung',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableActive',
                    'caption' => 'Aktiv'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableStatus',
                    'caption' => 'Status'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableDeviceStatusUpdate',
                    'caption' => 'Gerätestatus aktualisieren'
                ]
            ]
        ];

        ##### Actions

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => 'Schaltelemente'
            ];

        // Test center
        $form['actions'][] =
            [
                'type' => 'TestCenter'
            ];

        // Device status update
        $form['actions'][] =
            [
                'type'    => 'Button',
                'caption' => 'Gerätestatus aktualisieren',
                'onClick' => self::MODULE_PREFIX . '_UpdateDeviceStatus(' . $this->InstanceID . ');'
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        // Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        $amountReferences = count($references);
        if ($amountReferences == 0) {
            $amountReferences = 3;
        }
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $location = '';
            $rowColor = '#FFC0C0'; // red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $location = IPS_GetLocation($reference);
                $rowColor = '#C0FFC0'; // light green
            }
            $registeredReferences[] = [
                'ObjectID'         => $reference,
                'Name'             => $name,
                'VariableLocation' => $location,
                'rowColor'         => $rowColor];
        }

        // Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        $amountMessages = count($messages);
        if ($amountMessages == 0) {
            $amountMessages = 3;
        }
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $location = '';
            $rowColor = '#FFC0C0'; // red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $location = IPS_GetLocation($id);
                $rowColor = '#C0FFC0'; // light green
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $registeredMessages[] = [
                'ObjectID'           => $id,
                'Name'               => $name,
                'VariableLocation'   => $location,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription,
                'rowColor'           => $rowColor];
        }

        // Developer area
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Entwicklerbereich',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Referenzen',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredReferences',
                    'rowCount' => $amountReferences,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " bearbeiten", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                        ],
                        [
                            'caption' => 'Objektbaum',
                            'name'    => 'VariableLocation',
                            'width'   => '700px'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'visible'  => false,
                    'objectID' => 0
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Nachrichten',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'rowCount' => $amountMessages,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " bearbeiten", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                        ],
                        [
                            'caption' => 'Objektbaum',
                            'name'    => 'VariableLocation',
                            'width'   => '700px'
                        ],
                        [
                            'caption' => 'Nachrichten ID',
                            'name'    => 'MessageID',
                            'width'   => '150px'
                        ],
                        [
                            'caption' => 'Nachrichten Bezeichnung',
                            'name'    => 'MessageDescription',
                            'width'   => '250px'
                        ]
                    ],
                    'values' => $registeredMessages
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredMessagesConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        // Info message
        $form['actions'][] =
            [
                'type'    => 'PopupAlert',
                'name'    => 'InfoMessage',
                'visible' => false,
                'popup'   => [
                    'closeCaption' => 'OK',
                    'items'        => [
                        [
                            'type'    => 'Label',
                            'name'    => 'InfoMessageLabel',
                            'caption' => '',
                            'visible' => true
                        ]
                    ]
                ]
            ];

        ##### Status

        $form['status'][] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' wird erstellt',
        ];
        $form['status'][] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' ist aktiv',
        ];
        $form['status'][] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' wird gelöscht',
        ];
        $form['status'][] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => $module['ModuleName'] . ' ist inaktiv',
        ];
        $form['status'][] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug!',
        ];

        return json_encode($form);
    }

    ##### Private

    private function CountRows(string $ListName): int
    {
        $elements = json_decode($this->ReadPropertyString($ListName), true);
        $amountRows = count($elements) + 1;
        if ($amountRows == 1) {
            $amountRows = 3;
        }
        return $amountRows;
    }

    private function GetRowColors(string $ListName): array
    {
        $values = [];
        $elements = json_decode($this->ReadPropertyString($ListName), true);
        foreach ($elements as $element) {
            $rowColor = '#C0FFC0'; // light green
            if (!$element['Use']) {
                $rowColor = '#DFDFDF'; // grey
            }
            if ($ListName == 'Actions') {
                $action = json_decode($element['Action'], true);
                if (array_key_exists('parameters', $action)) {
                    if (array_key_exists('TARGET', $action['parameters'])) {
                        $id = $action['parameters']['TARGET'];
                        if (@!IPS_ObjectExists($id)) {
                            $rowColor = '#FFC0C0'; // red
                        }
                    }
                }
            } else {
                if (array_key_exists('ID', $element)) {
                    if (@!IPS_ObjectExists($element['ID'])) {
                        $rowColor = '#FFC0C0'; // red
                    }
                }
            }
            $values[] = ['rowColor' => $rowColor];
        }
        return $values;
    }

    private function CountElements(string $ListName): int
    {
        return count(json_decode($this->ReadPropertyString($ListName), true));
    }
}