<table>
    <thead>
        <tr>
            <th colspan="36" style="text-align: center; color: #166534; font-size: 16px; font-weight: bold;">
                FORMATO DE ASISTENCIA AMAZON NUTS SAC
            </th>
        </tr>
        <tr>
            <th colspan="36" style="text-align: center; color: #4b5563; font-size: 12px; font-weight: bold;">
                ASISTENCIA MES DE {{ mb_strtoupper($mes) }}
            </th>
        </tr>
        <tr>
            <th colspan="36"></th> </tr>

        <tr>
            <th rowspan="2" style="background-color: #f8fafc; border: 1px solid #000000; font-weight: bold; text-align: center; vertical-align: middle;">N°</th>
            <th rowspan="2" style="background-color: #f8fafc; border: 1px solid #000000; font-weight: bold; text-align: center; vertical-align: middle;">APELLIDOS Y NOMBRES</th>
            <th rowspan="2" style="background-color: #f8fafc; border: 1px solid #000000; font-weight: bold; text-align: center; vertical-align: middle;">DNI</th>
            
            @foreach($dias as $dia)
                <th style="background-color: {{ $dia->dayOfWeek == 0 ? '#e5e7eb' : '#f8fafc' }}; border: 1px solid #000000; font-weight: bold; text-align: center;">
                    {{ $dia->format('j') }}
                </th>
            @endforeach
            
            <th rowspan="2" style="background-color: #f8fafc; border: 1px solid #000000; font-weight: bold; text-align: center; vertical-align: middle;">HORAS DE TRABAJO</th>
            <th rowspan="2" style="background-color: #f8fafc; border: 1px solid #000000; font-weight: bold; text-align: center; vertical-align: middle;">OBS.</th>
        </tr>
        
        <tr>
            @php $letras = [1=>'L', 2=>'M', 3=>'M', 4=>'J', 5=>'V', 6=>'S', 0=>'D']; @endphp
            @foreach($dias as $dia)
                <th style="background-color: {{ $dia->dayOfWeek == 0 ? '#e5e7eb' : '#f8fafc' }}; border: 1px solid #000000; font-weight: bold; text-align: center;">
                    {{ $letras[$dia->dayOfWeek] }}
                </th>
            @endforeach
        </tr>
    </thead>
    
    <tbody>
        @foreach($matriz as $index => $fila)
            <tr>
                <td style="border: 1px solid #000000; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000; text-align: left;">{{ mb_strtoupper($fila['nombre']) }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $fila['dni'] }}</td>
                
                @foreach($dias as $dia)
                    @php
                        $fechaStr = $dia->format('Y-m-d');
                        $esDomingo = ($dia->dayOfWeek == 0);
                        $letra = $fila['asistencias'][$fechaStr] ?? '';
                        $mostrar = ($letra === '-' || $letra === '') ? ($esDomingo ? 'D' : '') : $letra;
                        
                        // Lógica de colores para Excel
                        $colorTexto = '#000000';
                        $negrita = 'normal';
                        if ($mostrar === 'F') { $colorTexto = '#dc2626'; $negrita = 'bold'; } // Rojo Falta
                        if ($mostrar === 'T') { $colorTexto = '#ea580c'; $negrita = 'bold'; } // Naranja Tardanza
                        if ($mostrar === 'D') { $colorTexto = '#6b7280'; } // Gris Descanso
                        
                        $bgColor = $esDomingo ? '#e5e7eb' : '#ffffff';
                    @endphp
                    
                    <td style="border: 1px solid #000000; text-align: center; background-color: {{ $bgColor }}; color: {{ $colorTexto }}; font-weight: {{ $negrita }};">
                        {{ $mostrar }}
                    </td>
                @endforeach
                
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; background-color: #f0fdf4;">
                    {{ $fila['total_horas'] }}
                </td>
                
                <td style="border: 1px solid #000000; text-align: center;"></td>
            </tr>
        @endforeach
    </tbody>
</table>