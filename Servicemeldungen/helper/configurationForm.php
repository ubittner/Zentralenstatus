<?php

declare(strict_types=1);

trait ConfigurationForm
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
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) { //0 = main category, 1 = none
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', $Caption);
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $ObjectID);
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

        // Number of messages
        $numberOfMessages = $this->ReadPropertyInteger('NumberOfMessages');
        $enableNumberOfMessagesConfig = false;
        if (@IPS_VariableExists($numberOfMessages)) {
            $enableNumberOfMessagesConfig = true;
        }

        // Service texts
        $serviceTexts = $this->ReadPropertyInteger('ServiceTexts');
        $enableServiceTextsConfig = false;
        if (@IPS_VariableExists($serviceTexts)) {
            $enableServiceTextsConfig = true;
        }

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel2',
            'caption' => 'Servicemeldungen',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte wählen Sie die (System-)Variablen aus',
                    'bold'    => true
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'NumberOfMessages',
                            'caption'  => 'Servicemeldungsanzahl (optional)',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "NumberOfMessagesConfigurationButton", "ID " . $NumberOfMessages . " bearbeiten", $NumberOfMessages);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'NumberOfMessagesConfigurationButton',
                            'caption'  => 'ID ' . $numberOfMessages . ' bearbeiten',
                            'visible'  => $enableNumberOfMessagesConfig,
                            'objectID' => $numberOfMessages
                        ]
                    ]
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'ServiceTexts',
                            'caption'  => 'Servicetexte',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "ServiceTextsConfigurationButton", "ID " . $ServiceTexts . " bearbeiten", $ServiceTexts);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'ServiceTextsConfigurationButton',
                            'caption'  => 'ID ' . $serviceTexts . ' bearbeiten',
                            'visible'  => $enableServiceTextsConfig,
                            'objectID' => $serviceTexts
                        ]
                    ]
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'ServiceTextsDelimiter',
                    'caption' => 'Abgrenzungszeichen der einzelnen Servicetexte'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'ServiceTextDelimiter',
                    'caption' => 'Abgrenzungszeichen innerhalb der Servicetexte'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'LogServiceMessages',
                    'caption' => 'Servicemeldungen protokollieren'
                ]
            ]
        ];

        // Status designations
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel3',
            'caption' => 'Statusbezeichnungen',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte benennen Sie die Statusbezeichnungen',
                    'bold'    => true
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'StatusServiceMessages',
                    'caption' => 'Servicemeldung(en) vorhanden'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'StatusNoServiceMessages',
                    'caption' => 'Keine Servicemeldung(en) vorhanden'
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
                    'type'    => 'Label',
                    'caption' => 'Nachricht (WebFront Visualisierung)',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'Notification',
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
                            'caption' => 'Icon',
                            'name'    => 'Icon',
                            'width'   => '200px',
                            'add'     => 'Warning',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'Title',
                            'width'   => '350px',
                            'add'     => 'Servicemeldung',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '200px',
                            'add'     => '🟡 %1$s, %2$s, %3$s, %4$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Platzhalter: %1$s = Name, %2$s = Seriennummer, %3$s = Servicemeldung, %4$s = Datum / Uhrzeit',
                            'name'    => 'TextInfo',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'DisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ],
                        [
                            'caption' => 'Batterieladezustand gering',
                            'name'    => 'UseServiceMessageLowBattery',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Gerätekommunikation gestört',
                            'name'    => 'UseServiceMessageUnreach',
                            'width'   => '250px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sabotage',
                            'name'    => 'UseServiceMessageSabotage',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sonstige',
                            'name'    => 'UseServiceMessageOther',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
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
                    'type'    => 'Label',
                    'caption' => 'Push-Nachricht (WebFront Visualisierung)',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'PushNotification',
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
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'Title',
                            'width'   => '350px',
                            'add'     => 'Servicemeldung',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '200px',
                            'add'     => '🟡 %1$s, %2$s, %3$s, %4$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Platzhalter: %1$s = Name, %2$s = Seriennummer, %3$s = Servicemeldung, %4$s = Datum / Uhrzeit',
                            'name'    => 'TextInfo',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'Sound',
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
                            'name'    => 'TargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
                            ]
                        ],
                        [
                            'caption' => 'Batterieladezustand gering',
                            'name'    => 'UseServiceMessageLowBattery',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Gerätekommunikation gestört',
                            'name'    => 'UseServiceMessageUnreach',
                            'width'   => '250px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sabotage',
                            'name'    => 'UseServiceMessageSabotage',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sonstige',
                            'name'    => 'UseServiceMessageOther',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ]
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
                    'type'    => 'Label',
                    'caption' => 'Post-Nachricht (Kachel Visualisierung)',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'PostNotification',
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
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'Title',
                            'width'   => '350px',
                            'add'     => 'Servicemeldung',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '200px',
                            'add'     => '🟡 %1$s, %2$s, %3$s, %4$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Platzhalter: %1$s = Name, %2$s = Seriennummer, %3$s = Servicemeldung, %4$s = Datum / Uhrzeit',
                            'name'    => 'TextInfo',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'Icon',
                            'width'   => '200px',
                            'add'     => 'Warning',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'Sound',
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
                            'name'    => 'TargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
                            ]
                        ],
                        [
                            'caption' => 'Batterieladezustand gering',
                            'name'    => 'UseServiceMessageLowBattery',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Gerätekommunikation gestört',
                            'name'    => 'UseServiceMessageUnreach',
                            'width'   => '250px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sabotage',
                            'name'    => 'UseServiceMessageSabotage',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sonstige',
                            'name'    => 'UseServiceMessageOther',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ]
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
                    'type'    => 'Label',
                    'caption' => 'E-Mail (Mailer)',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'MailerNotification',
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
                            'caption' => 'Betreff',
                            'name'    => 'Subject',
                            'width'   => '350px',
                            'add'     => 'Servicemeldung',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'Text',
                            'width'   => '200px',
                            'add'     => '🟡 %1$s, %2$s, %3$s, %4$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Platzhalter: %1$s = Name, %2$s = Seriennummer, %3$s = Servicemeldung, %4$s = Datum / Uhrzeit',
                            'name'    => 'TextInfo',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterieladezustand gering',
                            'name'    => 'UseServiceMessageLowBattery',
                            'width'   => '230px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Gerätekommunikation gestört',
                            'name'    => 'UseServiceMessageUnreach',
                            'width'   => '250px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sabotage',
                            'name'    => 'UseServiceMessageSabotage',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sonstige',
                            'name'    => 'UseServiceMessageOther',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
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
                    'type'    => 'Label',
                    'caption' => 'Bitte wählen Sie aus, welche Variablen in der Visualisierung angezeigt werden sollen',
                    'bold'    => true
                ],
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
                    'name'    => 'EnableLastUpdate',
                    'caption' => 'Letzte Aktualisierung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableUpdateStatus',
                    'caption' => 'Aktualisierung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableNumberOfMessages',
                    'caption' => 'Servicemeldungsanzahl'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableMessageList',
                    'caption' => 'Servicemeldungen'
                ]
            ]
        ];

        ##### Actions

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => 'Schaltelemente'
            ];

        // Testcenter
        $form['actions'][] =
            [
                'type'     => 'CheckBox',
                'name'     => 'ActiveSwitch',
                'caption'  => 'Aktiv',
                'value'    => $this->GetValue('Active'),
                'onChange' => self::MODULE_PREFIX . '_SwitchActive($id, $ActiveSwitch);'
            ];

        $form['actions'][] =
            [
                'type'    => 'Button',
                'caption' => 'Aktualisieren',
                'onClick' => self::MODULE_PREFIX . '_UpdateData($id);'
            ];

        $form['actions'][] =
        [
            'type'    => 'PopupButton',
            'caption' => 'Servicemeldungen anzeigen',
            'popup'   => [
                'caption' => 'Servicemeldungen',
                'items'   => [
                    [
                        'type'     => 'List',
                        'name'     => 'ServiceMessagesList',
                        'add'      => false,
                        'rowCount' => 1,
                        'columns'  => [
                            [
                                'name'    => 'Status',
                                'caption' => 'Status',
                                'width'   => '100px',
                                'save'    => false
                            ],
                            [
                                'name'    => 'Name',
                                'caption' => 'Name',
                                'width'   => '300px',
                                'save'    => false
                            ],
                            [
                                'name'    => 'SerialNumber',
                                'caption' => 'Seriennummer',
                                'width'   => '300px',
                                'save'    => false
                            ],
                            [
                                'name'    => 'ServiceMessage',
                                'caption' => 'Servicemeldung',
                                'width'   => '400px',
                                'save'    => false
                            ],
                            [
                                'name'    => 'DateTime',
                                'caption' => 'Datum / Uhrzeit',
                                'width'   => '200px',
                                'save'    => false
                            ],
                        ]
                    ]
                ]
            ],
            'onClick' => self::MODULE_PREFIX . '_ShowServiceMessages($id);'
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
            $id = $element['ID'];
            if (@!IPS_ObjectExists($id)) {
                $rowColor = '#FFC0C0'; //red
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