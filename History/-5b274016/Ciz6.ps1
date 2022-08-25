Import-Module posh-git
Import-Module oh-my-posh
Import-Module PSReadLine
Import-Module Terminal-Icons

oh-my-posh --init --shell pwsh --config ~/Documents/PowerShell/bubbles.omp.json | Invoke-Expression

# set-location "~/../../"
clear

# Alias
Set-Alias vim nvim
Set-Alias ll ls
Set-Alias g git
Set-ALias grep findstr

# Shows navigable menu of all options when hitting Tab
Set-PSReadLineKeyHandler -Key Tab -Function MenuComplete
# Autocompleteion for Arrow keys
Set-PSReadLineOption -HistorySearchCursorMovesToEnd
Set-PSReadLineKeyHandler -Key UpArrow -Function HistorySearchBackward
Set-PSReadLineKeyHandler -Key DownArrow -Function HistorySearchForward
Set-PSReadLineOption -ShowToolTips
Set-PSReadLineOption -PredictionSource History

# jumper to htdocs
function htdocs { set-location "C:\laragon\www" }
