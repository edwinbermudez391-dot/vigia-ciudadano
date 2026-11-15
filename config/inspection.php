<?php

// Criterios operativos iniciales. La dependencia responsable debe validarlos
// frente al alcance y las especificaciones particulares de cada intervención.
return [
    'common' => [
        'zona_limpia' => 'Zona intervenida limpia y sin residuos de la reparación',
        'paso_seguro' => 'Paso peatonal y vehicular seguro, sin riesgos visibles por la intervención',
    ],
    'categories' => [
        'Baches y Malla Vial' => [
            'superficie_uniforme' => 'Reparación nivelada y sin huecos ni material suelto visible',
            'juntas_selladas' => 'Uniones de la reparación cerradas, sin desprendimientos visibles',
            'drenaje_libre' => 'La reparación no obstruye sumideros ni el recorrido del agua',
        ],
        'Grietas en Fachadas y Muros' => [
            'intervencion_tecnica' => 'La intervención corresponde al diagnóstico y solución del responsable técnico',
            'zona_reparada' => 'Las grietas incluidas en la orden fueron intervenidas según la especificación',
            'elementos_estables' => 'No hay piezas sueltas ni desprendimientos visibles en el área intervenida',
        ],
        'Andenes Deteriorados' => [
            'superficie_nivelada' => 'Andén reparado sin huecos, baldosas sueltas ni desniveles peligrosos visibles',
            'acceso_continuo' => 'El recorrido peatonal y los accesos existentes quedan libres de obstáculos',
            'drenaje_libre' => 'La intervención no bloquea el desagüe ni genera empozamientos visibles',
        ],
        'Semáforo en Falla' => [
            'luces_operativas' => 'Luces del semáforo intervenido funcionando durante la prueba',
            'ciclo_verificado' => 'Secuencia y coordinación comprobadas por el responsable de movilidad',
            'equipos_seguros' => 'Soportes y cerramientos firmes, sin cableado accesible expuesto',
        ],
        'Señalización Vial Deteriorada' => [
            'senal_legible' => 'Señal legible, visible y sin daños en la cara informativa',
            'ubicacion_correcta' => 'Ubicación y orientación coinciden con la orden y la revisión de movilidad',
            'fijacion_firme' => 'Soporte y fijaciones estables, sin piezas sueltas',
        ],
        'Congestionamiento Vial Anormal' => [
            'causa_atendida' => 'La obstrucción o causa incluida en la orden fue atendida',
            'circulacion_restablecida' => 'Circulación restablecida o desvío autorizado y operativo',
            'senalizacion_segura' => 'Señalización de control visible y accesos de emergencia libres',
        ],
        'Luminaria Apagada o en Falla' => [
            'encendido_verificado' => 'Luminaria enciende y permanece estable durante la prueba',
            'fijacion_firme' => 'Luminaria y soporte correctamente fijados, sin piezas sueltas visibles',
            'conexion_protegida' => 'Conexiones y cerramientos protegidos según revisión del personal autorizado',
        ],
        'Cableado Expuesto' => [
            'cableado_protegido' => 'Cableado intervenido protegido y fuera del alcance de las personas',
            'soportes_firmes' => 'Canalizaciones y soportes de la intervención firmes y cerrados',
            'revision_autorizada' => 'Personal autorizado verificó la seguridad y el servicio de la instalación',
        ],
        'Árbol en Riesgo de Caída' => [
            'riesgo_atendido' => 'El riesgo identificado fue atendido conforme a la evaluación del personal competente',
            'intervencion_autorizada' => 'Poda, estabilización o retiro corresponden a la intervención autorizada',
            'entorno_revisado' => 'Se revisaron afectaciones visibles a redes, vías y predios cercanos',
        ],
        'Basuras y Escombros en Vía Pública' => [
            'residuos_retirados' => 'Se retiraron los residuos y escombros incluidos en el reporte',
            'disposicion_registrada' => 'El retiro y destino de los residuos están registrados por el responsable',
            'desagues_libres' => 'Sumideros y accesos de la zona quedan sin residuos que los obstruyan',
        ],
        'Contaminación Auditiva' => [
            'fuente_atendida' => 'Se atendió la fuente de ruido identificada en la orden',
            'verificacion_registrada' => 'Se registró la verificación del responsable, con medición cuando corresponda',
            'medida_aplicada' => 'La medida correctiva acordada está aplicada al momento de la visita',
        ],
        'Fuga de Agua Potable' => [
            'fuga_detenida' => 'No se observa fuga en el punto reparado durante la prueba',
            'servicio_verificado' => 'El operador verificó el servicio después de la reparación',
            'zona_restituida' => 'Excavación cerrada y superficie intervenida restituida según la orden',
        ],
        'Alcantarillado o Sumidero Tapado' => [
            'flujo_restablecido' => 'Flujo del agua restablecido durante la comprobación',
            'rejilla_segura' => 'Tapas y rejillas intervenidas colocadas y estables',
            'sin_rebose' => 'Sin rebose visible en el punto atendido durante la prueba',
        ],
        'Sin Suministro de Agua' => [
            'suministro_restablecido' => 'Suministro comprobado en la zona o punto afectado',
            'operador_confirma' => 'El operador registró la verificación del servicio y las condiciones de entrega',
            'sin_fugas_visibles' => 'No hay fugas visibles generadas por la intervención',
        ],
    ],
    'fallback' => [
        'problema_atendido' => 'El problema descrito en el reporte fue atendido',
        'alcance_verificado' => 'El trabajo realizado coincide con el alcance de la orden',
        'funcionamiento_verificado' => 'El responsable comprobó el resultado de la intervención',
    ],
];
