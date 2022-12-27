# Zentralenstatus

Zur Verwendung dieses Moduls als Privatperson, Einrichter oder Integrator wenden Sie sich bitte zunächst an den Autor.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.


### Inhaltsverzeichnis

1. [Modulbeschreibung](#1-modulbeschreibung)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Schaubild](#3-schaubild)
4. [HomeMatic Socket](#4-HomeMatic Socket)
5. [PHP-Befehlsreferenz](#5-php-befehlsreferenz)
   1. [Batteriestatus prüfen](#51-status-prüfen)

### 1. Modulbeschreibung

Dieses Modul überwacht den Verbindungsstatus einer Homematic CCU Zentrale in [IP-Symcon](https://www.symcon.de).  
Sollte die Verbindung zur Homematic CCU Zentrale verloren gehen, so versucht das Modul die Verbindung nach einer definierten Zeit wiederherzustellen.  
Ist die Verbindung wiederhergestellt, kann außerdem der aktuelle Gerätestatus der Sensoren/Aktoren angefordert werden. 

### 2. Voraussetzungen

- IP-Symcon ab Version 6.1

### 3. Schaubild

```
                              +-------------------------+
                              | Zentralenstatus (Modul) |
                              |                         |
HomeMatic Socket<-------------+ Status                  |
                              |                         |
                              +-------------------------+
```

### 4. HomeMatic Socket

Das Modul reagiert auf Statusänderungen von einer HomeMatic Socket I/O Instanz.
Sofern die Verbindung wieder hergestellt wurde, können weitere Aktionen ausgeführt werden.

### 5. PHP-Befehlsreferenz

#### 5.1 Status prüfen

```
void ZENS_CheckStatus(integer INSTANCE_ID);
```

Der Befehl liefert keinen Rückgabewert.

| Parameter     | Wert  | Bezeichnung    |
|---------------|-------|----------------|
| `INSTANCE_ID` |       | ID der Instanz |

Beispiel:  
> ZENS_CheckStatus(12345);
