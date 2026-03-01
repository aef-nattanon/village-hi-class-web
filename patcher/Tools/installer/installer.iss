[Setup]
AppName=Ragnarok Village Server
AppVersion=1.0
AppPublisher=Village Team
AppPublisherURL=https://yourwebsite.com
DefaultDirName={autopf}\RagnarokVillage
DefaultGroupName=RagnarokVillage
OutputBaseFilename=RagnarokVillageSetup
Compression=lzma2
SolidCompression=yes
WizardStyle=modern
SetupIconFile="C:\Users\Nattanon\OneDrive\Desktop\logo.ico"
PrivilegesRequired=admin
DisableProgramGroupPage=yes
WizardImageFile=bg_ragnarok2.png
WizardSmallImageFile=bg_ragnarok4.png
; DiskSpanning=yes
; DiskSliceSize=max

DiskSpanning=yes
DiskSliceSize=2000000000
OutputDir=output

VersionInfoVersion=1.0.0.0
VersionInfoCompany=Village Server
VersionInfoDescription=Ragnarok Village Installer

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "Create a &desktop icon"; GroupDescription: "Additional icons:"; Flags: unchecked

[Files]
Source: "C:\Users\Nattanon\Code\ragnarok\Village Client\Village Client\*"; DestDir: "{app}"; Flags: recursesubdirs ignoreversion
 
[Icons]
Name: "{group}\Village Hi-Class"; Filename: "{app}\Patcher.exe"
Name: "{autodesktop}\Village Hi-Class"; Filename: "{app}\Patcher.exe"; Tasks: desktopicon

[Run]
Filename: "{app}\Patcher.exe"; Description: "Launch Ragnarok Village"; Flags: nowait postinstall skipifsilent
