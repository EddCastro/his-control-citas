# Genera EVIDENCIA.md con salidas reales del entorno (RQNF-08).
#
# Uso, desde la raiz del repositorio y con Docker Desktop abierto:
#   powershell -ExecutionPolicy Bypass -File .\scripts\evidencia.ps1
#
# Levanta el entorno, ejecuta las llamadas a la API con curl, prueba la
# concurrencia y la persistencia, corre las pruebas automatizadas y agrega
# docker ps y git log --graph. Todo queda en EVIDENCIA.md y en docs/evidencia/salidas.

param([string]$Base = "http://localhost:8000")

$ErrorActionPreference = "Continue"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
$utf8 = New-Object System.Text.UTF8Encoding $false

$raiz = Split-Path -Parent $PSScriptRoot
Set-Location $raiz
$salidas = Join-Path $raiz "docs/evidencia/salidas"
New-Item -ItemType Directory -Force -Path $salidas | Out-Null
$tmp = Join-Path ([IO.Path]::GetTempPath()) "his-evidencia"
New-Item -ItemType Directory -Force -Path $tmp | Out-Null

$md = New-Object System.Collections.Generic.List[string]
$n = 0

function Agregar([string]$texto) { $script:md.Add($texto) }

function Seccion([string]$titulo, [string]$comando, [scriptblock]$accion) {
    $script:n++
    Write-Host ("[{0:00}] {1}" -f $script:n, $titulo) -ForegroundColor Cyan
    $resultado = (& $accion 2>&1 | Out-String).TrimEnd()
    $archivo = "{0:00}-{1}.txt" -f $script:n, (($titulo.ToLower() -replace '[^a-z0-9]+', '-').Trim('-'))
    [IO.File]::WriteAllText((Join-Path $salidas $archivo), "PS> $comando`n$resultado`n", $utf8)
    Agregar "### $($script:n). $titulo"
    Agregar ""
    Agregar '```text'
    Agregar "PS> $comando"
    Agregar $resultado
    Agregar '```'
    Agregar ""
    return $resultado
}

# Ejecuta un comando externo a traves de cmd para capturar stdout y stderr como texto.
function Ejecutar([string]$linea) { cmd /c "$linea 2>&1" }

function Api([string]$metodo, [string]$ruta, $cuerpo = $null) {
    $argumentos = @('-s', '-X', $metodo, "$Base/api$ruta", '-H', 'Accept: application/json', '-w', "`nHTTP %{http_code}")
    if ($null -ne $cuerpo) {
        $archivo = Join-Path $tmp ("cuerpo-" + [guid]::NewGuid().ToString() + ".json")
        [IO.File]::WriteAllText($archivo, ($cuerpo | ConvertTo-Json -Compress), $utf8)
        $argumentos += @('-H', 'Content-Type: application/json', '--data-binary', "@$archivo")
    }
    & curl.exe @argumentos
}

function ComandoCurl([string]$metodo, [string]$ruta, $cuerpo = $null) {
    $c = "curl.exe -s -X $metodo $Base/api$ruta -H 'Accept: application/json'"
    if ($null -ne $cuerpo) { $c += " -H 'Content-Type: application/json' -d '$($cuerpo | ConvertTo-Json -Compress)'" }
    return $c
}

function LlamarApi([string]$titulo, [string]$metodo, [string]$ruta, $cuerpo = $null) {
    return Seccion $titulo (ComandoCurl $metodo $ruta $cuerpo) { Api $metodo $ruta $cuerpo }
}

function IdDe([string]$respuesta) {
    $json = ($respuesta -split "`nHTTP")[0]
    return ($json | ConvertFrom-Json).data.id
}

function EsperarApp {
    Write-Host "Esperando a que la aplicacion responda en $Base ..." -ForegroundColor Yellow
    for ($i = 0; $i -lt 120; $i++) {
        try {
            $r = Invoke-WebRequest -UseBasicParsing -Uri "$Base/up" -TimeoutSec 5
            if ($r.StatusCode -eq 200) { return $true }
        } catch { }
        Start-Sleep -Seconds 5
    }
    return $false
}

# Fecha futura de lunes a viernes, lejos de los datos semilla.
$dia = (Get-Date).AddDays(21)
while ($dia.DayOfWeek -eq 'Saturday' -or $dia.DayOfWeek -eq 'Sunday') { $dia = $dia.AddDays(1) }
$F = $dia.ToString('yyyy-MM-dd')
$desde = (Get-Date).ToString('yyyy-MM-dd')
$hasta = (Get-Date).AddDays(30).ToString('yyyy-MM-dd')

# ------------------------------------------------------------------ encabezado
Agregar "# Evidencia — Serie II, Control de Citas Médicas"
Agregar ""
Agregar "Generado por ``scripts/evidencia.ps1`` el $(Get-Date -Format 'yyyy-MM-dd HH:mm') en el equipo del estudiante."
Agregar "Todas las salidas son reales; cada una se guarda también en ``docs/evidencia/salidas/``."
Agregar ""
Agregar "| Dato | Información |"
Agregar "|---|---|"
Agregar "| Estudiante | Eddy Adolfo Castro Véliz |"
Agregar "| Código | 1890-23-16857 |"
Agregar "| Repositorio | https://github.com/EddCastro/his-control-citas |"
Agregar ""
Agregar "## Trazabilidad requisito → evidencia"
Agregar ""
Agregar "| Requisito | Evidencia |"
Agregar "|---|---|"
Agregar "| RQF-01 Crear cita | Salida 9, captura 02 y 03 |"
Agregar "| RQF-02 Calendario mes y semana | Capturas 01 y 09 |"
Agregar "| RQF-03 Impedir doble reserva | Salidas 11, 12 y 22; captura 04 |"
Agregar "| RQF-04 Reprogramar con drag & drop | Salida 15; capturas 07 y 08 |"
Agregar "| RQF-05 Cancelar sin eliminar | Salidas 18, 19, 20 y 21 |"
Agregar "| RQF-06 Filtrar por doctor y rango | Salida 8; capturas 10 y 11 |"
Agregar "| RQF-07 API CRUD y lectura de doctores/pacientes | Salidas 6 a 21 |"
Agregar "| RQF-08 Validación de entrada | Salidas 13 y 18 |"
Agregar "| RQF-09 Detalle al hacer clic | Salida 10; captura 05 |"
Agregar "| RQF-10 Color por estado | Capturas 01, 05 y 09 |"
Agregar "| RQNF-01 MySQL en Docker con volumen | Salidas 2, 3, 4 y 23 |"
Agregar "| RQNF-02 Un solo comando | Salida 1 |"
Agregar "| RQNF-03 JSON y códigos HTTP | Salidas 6 a 21: 200, 201, 400 (13, 18), 404 (14), 409 (11, 12, 17, 20) |"
Agregar "| RQNF-04 Capas | README, sección Arquitectura |"
Agregar "| RQNF-05 Trazabilidad Git | Salidas 25 y 26 |"
Agregar "| RQNF-06 Escritorio y tableta | Captura 12 |"
Agregar "| RQNF-07 Validación en el servidor | Salidas 11, 12 y 22 (concurrencia) |"
Agregar "| RQNF-08 Evidencia documentada | Este archivo |"
Agregar ""
Agregar "## Capturas del calendario"
Agregar ""
Agregar "| # | Captura | Qué muestra |"
Agregar "|---|---|---|"
$capturas = @(
    @('01-calendario-semana.png', 'Vista semanal con colores por estado'),
    @('02-crear-cita-formulario.png', 'Formulario abierto al seleccionar un horario'),
    @('03-cita-creada.png', 'Cita creada y aviso de confirmación'),
    @('04-conflicto-409-al-crear.png', 'Conflicto de horario (409) al crear'),
    @('05-detalle-cita.png', 'Detalle con estado, historial y acciones'),
    @('06-cancelar-exige-motivo.png', 'Cancelar sin motivo es rechazado'),
    @('07-drag-drop-reprogramada.png', 'Reprogramación por arrastre guardada'),
    @('08-drag-drop-conflicto-revertido.png', 'Arrastre hacia horario ocupado: se revierte'),
    @('09-vista-mes-colores.png', 'Vista mensual con colores por estado'),
    @('10-filtro-por-doctor.png', 'Calendario filtrado por doctor'),
    @('11-listado-doctor-rango.png', 'Listado por doctor y rango de fechas'),
    @('12-tableta-820px.png', 'Diseño en tableta (820 px)')
)
$i = 0
foreach ($c in $capturas) { $i++; Agregar ("| {0:00} | [{1}](docs/evidencia/capturas/{1}) | {2} |" -f $i, $c[0], $c[1]) }
Agregar ""
Agregar "![Vista semanal](docs/evidencia/capturas/01-calendario-semana.png)"
Agregar ""
Agregar "## Comandos y salidas"
Agregar ""

# ------------------------------------------------------------------ entorno Docker
Agregar "## A. Entorno Docker (RQNF-01, RQNF-02)"
Agregar ""
Seccion "Levantar el entorno con un solo comando" "docker compose up -d --build" { Ejecutar "docker compose up -d --build" } | Out-Null
if (-not (EsperarApp)) { Write-Host "La aplicacion no respondio. Revise: docker compose logs app" -ForegroundColor Red; exit 1 }
Seccion "Contenedores en ejecucion" "docker ps" { Ejecutar 'docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}"' } | Out-Null
Seccion "Volumen persistente de MySQL" "docker volume inspect his-control-citas_mysql_data" { Ejecutar "docker volume ls --filter name=mysql_data"; Ejecutar 'docker volume inspect his-control-citas_mysql_data --format "Nombre: {{.Name}}  Montaje: {{.Mountpoint}}"' } | Out-Null
Seccion "Tablas y datos semilla en MySQL" "docker exec his-mysql mysql ... his_citas" {
    Ejecutar 'docker exec his-mysql mysql -uhis -phis_secret his_citas -e "SELECT VERSION() AS mysql; SHOW TABLES; SELECT (SELECT COUNT(*) FROM pacientes) AS pacientes, (SELECT COUNT(*) FROM doctores) AS doctores, (SELECT COUNT(*) FROM citas) AS citas; SELECT estado, COUNT(*) AS total FROM citas GROUP BY estado;"' | Where-Object { $_ -notmatch 'Using a password' }
} | Out-Null
Seccion "Esquema de la tabla citas" "docker exec his-mysql mysql ... SHOW CREATE TABLE citas" {
    Ejecutar 'docker exec his-mysql mysql -uhis -phis_secret his_citas -e "SHOW CREATE TABLE citas\G"' | Where-Object { $_ -notmatch 'Using a password' }
} | Out-Null

# ------------------------------------------------------------------ API
Agregar "## B. API REST (RQF-01 a RQF-09, RQNF-03)"
Agregar ""
LlamarApi "GET doctores" GET "/doctores" | Out-Null
LlamarApi "GET pacientes" GET "/pacientes" | Out-Null
LlamarApi "GET citas filtradas por doctor y rango de fechas" GET "/citas?doctor_id=1&desde=$desde&hasta=$hasta" | Out-Null

$cuerpo = [ordered]@{ paciente_id = 2; doctor_id = 3; fecha = $F; hora_inicio = '10:00'; hora_fin = '10:30'; motivo = 'Evaluación cardiológica de control' }
$creada = LlamarApi "POST crear cita (201)" POST "/citas" $cuerpo
$id = IdDe $creada

LlamarApi "GET detalle de la cita creada (200)" GET "/citas/$id" | Out-Null

$doble = [ordered]@{ paciente_id = 5; doctor_id = 3; fecha = $F; hora_inicio = '10:00'; hora_fin = '10:30'; motivo = 'Intento de doble reserva' }
LlamarApi "POST mismo doctor y horario (409 conflicto)" POST "/citas" $doble | Out-Null

$parcial = [ordered]@{ paciente_id = 6; doctor_id = 3; fecha = $F; hora_inicio = '10:15'; hora_fin = '10:45'; motivo = 'Solape parcial' }
LlamarApi "POST solape parcial (409 conflicto)" POST "/citas" $parcial | Out-Null

$invalido = [ordered]@{ paciente_id = 999; fecha = '05/10/2026'; hora_inicio = '11:00'; hora_fin = '10:00' }
LlamarApi "POST datos invalidos (400)" POST "/citas" $invalido | Out-Null

LlamarApi "GET cita inexistente (404)" GET "/citas/999999" | Out-Null

$nuevoHorario = [ordered]@{ fecha = $F; hora_inicio = '11:00'; hora_fin = '11:30' }
LlamarApi "PUT reprogramar cita (200)" PUT "/citas/$id" $nuevoHorario | Out-Null

LlamarApi "PATCH confirmar cita (200)" PATCH "/citas/$id/estado" ([ordered]@{ estado = 'confirmada' }) | Out-Null
LlamarApi "PATCH transicion invalida confirmada a pendiente (409)" PATCH "/citas/$id/estado" ([ordered]@{ estado = 'pendiente' }) | Out-Null
LlamarApi "PATCH cancelar sin motivo (400)" PATCH "/citas/$id/estado" ([ordered]@{ estado = 'cancelada' }) | Out-Null
LlamarApi "PATCH cancelar con motivo (200, el registro se conserva)" PATCH "/citas/$id/estado" ([ordered]@{ estado = 'cancelada'; motivo = 'El paciente no puede asistir' }) | Out-Null
LlamarApi "PUT reprogramar cita cancelada (409)" PUT "/citas/$id" $nuevoHorario | Out-Null
LlamarApi "POST mismo horario tras cancelar (201, el horario se libero)" POST "/citas" $doble | Out-Null

# ------------------------------------------------------------------ concurrencia
Agregar "## C. Validación en el servidor bajo concurrencia (RQF-03, RQNF-07)"
Agregar ""
Agregar "Seis solicitudes simultáneas para el mismo doctor y horario. Debe crearse una sola cita."
Agregar ""
$simultanea = [ordered]@{ paciente_id = 1; doctor_id = 4; fecha = $F; hora_inicio = '15:00'; hora_fin = '15:30'; motivo = 'Prueba de concurrencia' }
$archivoSim = Join-Path $tmp "simultanea.json"
[IO.File]::WriteAllText($archivoSim, ($simultanea | ConvertTo-Json -Compress), $utf8)
Seccion "6 solicitudes POST simultaneas al mismo horario" "Start-Job x6 curl.exe -X POST $Base/api/citas" {
    $trabajos = 1..6 | ForEach-Object {
        Start-Job -ArgumentList $Base, $archivoSim, $_ -ScriptBlock {
            param($b, $f, $k)
            $codigo = & curl.exe -s -o NUL -w "%{http_code}" -X POST "$b/api/citas" -H "Accept: application/json" -H "Content-Type: application/json" --data-binary "@$f"
            "Solicitud $k -> HTTP $codigo"
        }
    }
    $trabajos | Wait-Job | Receive-Job
    $trabajos | Remove-Job
    $lista = (& curl.exe -s "$Base/api/citas?doctor_id=4&desde=$F&hasta=$F" | ConvertFrom-Json).data | Where-Object { $_.hora_inicio -eq '15:00' }
    "Citas guardadas para doctor 4 el $F a las 15:00: $(@($lista).Count)"
} | Out-Null

# ------------------------------------------------------------------ persistencia
Agregar "## D. Persistencia del volumen (RQNF-01)"
Agregar ""
Seccion "Los datos sobreviven a docker compose down / up" "docker compose down; docker compose up -d" {
    $antes = docker exec his-mysql mysql -uhis -phis_secret his_citas -N -e "SELECT COUNT(*) FROM citas" 2>$null
    "Citas antes de detener: $antes"
    Ejecutar "docker compose down"
    Ejecutar "docker compose up -d"
    Start-Sleep -Seconds 5
    for ($k = 0; $k -lt 60; $k++) {
        $despues = docker exec his-mysql mysql -uhis -phis_secret his_citas -N -e "SELECT COUNT(*) FROM citas" 2>$null
        if ($despues) { break }
        Start-Sleep -Seconds 3
    }
    "Citas despues de levantar de nuevo: $despues"
} | Out-Null
EsperarApp | Out-Null

# ------------------------------------------------------------------ pruebas
Agregar "## E. Pruebas automatizadas"
Agregar ""
Seccion "Suite de pruebas automatizadas" "docker compose exec -T app php artisan test" { Ejecutar "docker compose exec -T app php artisan test" } | Out-Null

# ------------------------------------------------------------------ git
Agregar "## F. Historial Git (RQNF-05)"
Agregar ""
Seccion "Ramas" "git branch -a" { Ejecutar "git branch -a" } | Out-Null
Seccion "Historial con ramas y merges" "git log --graph --all --oneline --decorate" { Ejecutar "git log --graph --all --oneline --decorate" } | Out-Null

[IO.File]::WriteAllLines((Join-Path $raiz "EVIDENCIA.md"), $md, $utf8)
Write-Host "`nListo: EVIDENCIA.md y docs/evidencia/salidas/ generados." -ForegroundColor Green
