#### Executar o comando abaixo primeiro para permitir a execução de scripts pelo usuário corrente
#Set-ExecutionPolicy -ExecutionPolicy Unrestrict -Scope CurrentUser

# Obtém a lista de pastas em C:\Users
$folders = Get-ChildItem -Path C:\Users -Directory

# Itera sobre cada pasta
foreach ($folder in $folders) {
    # Verifica se o nome da pasta contém apenas caracteres numéricos
    if ($folder.Name -match '^\d+$') {
        $username = $folder.Name

        # Verifica se o usuário existe no domínio geoinova
        #$userExists = Get-AdUser -Filter { SamAccountName -eq $username } -Server "geoinova" -ErrorAction SilentlyContinue
	$userExists = 1

        if ($userExists) {

            Write-Output "Perfil $username"

            # Remove o usuário do domínio
            #Remove-AdUser -Identity $username -Server "geoinova" -Confirm:$false
            #Write-Output "Usuário $username removido do domínio."

            # Remove a pasta de perfil local
            $profilePath = Join-Path -Path "C:\Users" -ChildPath $username
            Remove-Item -Path $profilePath -Recurse -Force
            Write-Output "Pasta de perfil local $profilePath removida."

	    # Obtém o SID do usuário
	    $userSID = New-Object System.Security.Principal.NTAccount($username)
	    $sid = $userSID.Translate([System.Security.Principal.SecurityIdentifier]).Value
            # Exibe o SID
	    Write-Output "SID do usuário $username : $sid"

            #### Apaga o Registro deste usuário
	    $profileSID = $sid
	    $registryPath = "HKLM:\SOFTWARE\Microsoft\Windows NT\CurrentVersion\ProfileList\$profileSID"
            # Exclui a subchave do Registro
	    Remove-Item -Path $registryPath -Recurse -Force
	    # Exibe a informação
	    Write-Output "SID do usuário $userName : $sid foi apagado do sistema"

        }

	Write-Output '.'

    }
}