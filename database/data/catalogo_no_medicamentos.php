<?php

/**
 * Productos comunes de botica que NO son medicamentos (DIGEMID no los publica en datos abiertos).
 * Nombres genéricos, sin marca ni precio: cada botica pone su precio y su marca al comprar.
 * Formato: categoría => [[nombre, unidad base], ...]. Lo carga CompanyProductCatalog.
 */
return [
    'Material médico y curación' => [
        ['Alcohol etílico 70° 120 ml', 'Frasco'], ['Alcohol etílico 70° 250 ml', 'Frasco'], ['Alcohol etílico 70° 500 ml', 'Frasco'],
        ['Alcohol etílico 70° 1 L', 'Frasco'], ['Alcohol en gel 250 ml', 'Frasco'], ['Agua oxigenada 120 ml', 'Frasco'],
        ['Agua oxigenada 250 ml', 'Frasco'], ['Yodopovidona solución 120 ml', 'Frasco'], ['Clorhexidina solución 120 ml', 'Frasco'],
        ['Suero fisiológico (cloruro de sodio 0.9%) 1 L', 'Frasco'], ['Algodón hidrófilo 25 g', 'Unidad'], ['Algodón hidrófilo 50 g', 'Unidad'],
        ['Algodón hidrófilo 100 g', 'Unidad'], ['Algodón hidrófilo 500 g', 'Unidad'], ['Gasa estéril 10 x 10 cm', 'Sobre'],
        ['Gasa estéril 5 x 5 cm', 'Sobre'], ['Gasa en rollo', 'Unidad'], ['Venda elástica 2 pulgadas', 'Unidad'],
        ['Venda elástica 3 pulgadas', 'Unidad'], ['Venda elástica 4 pulgadas', 'Unidad'], ['Venda elástica 6 pulgadas', 'Unidad'],
        ['Venda de gasa 2 pulgadas', 'Unidad'], ['Venda de yeso 4 pulgadas', 'Unidad'], ['Esparadrapo de tela 1 pulgada', 'Unidad'],
        ['Esparadrapo hipoalergénico (micropore) 1 pulgada', 'Unidad'], ['Esparadrapo hipoalergénico (micropore) 1/2 pulgada', 'Unidad'],
        ['Curitas (apósitos adhesivos)', 'Unidad'], ['Apósito transparente', 'Unidad'], ['Jeringa descartable 1 ml', 'Unidad'],
        ['Jeringa descartable 3 ml', 'Unidad'], ['Jeringa descartable 5 ml', 'Unidad'], ['Jeringa descartable 10 ml', 'Unidad'],
        ['Jeringa descartable 20 ml', 'Unidad'], ['Jeringa de insulina 1 ml', 'Unidad'], ['Aguja hipodérmica 21G', 'Unidad'],
        ['Aguja hipodérmica 23G', 'Unidad'], ['Aguja hipodérmica 25G', 'Unidad'], ['Catéter endovenoso 20G', 'Unidad'],
        ['Catéter endovenoso 22G', 'Unidad'], ['Equipo de venoclisis', 'Unidad'], ['Guantes de látex descartables (par)', 'Unidad'],
        ['Guantes de nitrilo descartables (par)', 'Unidad'], ['Guantes quirúrgicos estériles (par)', 'Unidad'], ['Mascarilla quirúrgica descartable', 'Unidad'],
        ['Mascarilla KN95', 'Unidad'], ['Baja lengua de madera', 'Unidad'], ['Hisopos de algodón', 'Unidad'],
        ['Bolsa colectora de orina', 'Unidad'], ['Sonda Foley', 'Unidad'], ['Sonda Nelaton', 'Unidad'],
        ['Frasco para muestra de orina', 'Unidad'], ['Frasco para muestra de heces', 'Unidad'], ['Bolsa de agua caliente', 'Unidad'],
        ['Bolsa de hielo (compresa fría)', 'Unidad'], ['Collarín cervical', 'Unidad'], ['Tobillera elástica', 'Unidad'],
        ['Rodillera elástica', 'Unidad'], ['Muñequera elástica', 'Unidad'], ['Faja lumbar', 'Unidad'], ['Medias de compresión', 'Unidad'],
    ],
    'Dispositivos médicos' => [
        ['Termómetro digital', 'Unidad'], ['Termómetro infrarrojo', 'Unidad'], ['Termómetro de mercurio', 'Unidad'],
        ['Tensiómetro digital de brazo', 'Unidad'], ['Tensiómetro digital de muñeca', 'Unidad'], ['Tensiómetro aneroide con estetoscopio', 'Unidad'],
        ['Glucómetro', 'Unidad'], ['Tiras reactivas para glucómetro', 'Caja'], ['Lancetas para glucómetro', 'Caja'],
        ['Oxímetro de pulso', 'Unidad'], ['Nebulizador', 'Unidad'], ['Kit de nebulización (mascarilla)', 'Unidad'],
        ['Inhalocámara (aerocámara)', 'Unidad'], ['Estetoscopio', 'Unidad'], ['Balanza digital', 'Unidad'],
        ['Prueba rápida de embarazo', 'Unidad'], ['Prueba rápida de antígeno COVID-19', 'Unidad'], ['Prueba rápida de VIH', 'Unidad'],
        ['Silla de ruedas', 'Unidad'], ['Muletas (par)', 'Unidad'], ['Bastón ortopédico', 'Unidad'], ['Andador ortopédico', 'Unidad'],
        ['Pastillero semanal', 'Unidad'], ['Cortapastillas', 'Unidad'],
    ],
    'Cuidado personal e higiene' => [
        ['Jabón antibacterial en barra', 'Unidad'], ['Jabón líquido antibacterial', 'Frasco'], ['Jabón neutro', 'Unidad'],
        ['Shampoo anticaspa', 'Frasco'], ['Shampoo neutro', 'Frasco'], ['Pasta dental', 'Tubo'], ['Pasta dental para sensibilidad', 'Tubo'],
        ['Cepillo dental adulto', 'Unidad'], ['Cepillo dental infantil', 'Unidad'], ['Hilo dental', 'Unidad'], ['Enjuague bucal', 'Frasco'],
        ['Desodorante', 'Unidad'], ['Protector solar FPS 50', 'Frasco'], ['Protector solar facial FPS 50', 'Tubo'],
        ['Repelente de insectos', 'Frasco'], ['Crema humectante corporal', 'Frasco'], ['Crema para manos', 'Tubo'],
        ['Vaselina sólida', 'Unidad'], ['Bálsamo labial', 'Unidad'], ['Toallas higiénicas', 'Unidad'], ['Protectores diarios', 'Unidad'],
        ['Tampones', 'Unidad'], ['Copa menstrual', 'Unidad'], ['Jabón íntimo', 'Frasco'], ['Toallitas húmedas', 'Unidad'],
        ['Pañuelos desechables', 'Unidad'], ['Papel higiénico', 'Unidad'], ['Pañal para adulto talla M', 'Unidad'],
        ['Pañal para adulto talla L', 'Unidad'], ['Apósito para incontinencia', 'Unidad'], ['Gotas lubricantes oculares', 'Frasco'],
        ['Solución para lentes de contacto', 'Frasco'], ['Afeitadora descartable', 'Unidad'], ['Espuma de afeitar', 'Unidad'],
        ['Cortaúñas', 'Unidad'], ['Lima de uñas', 'Unidad'], ['Peine', 'Unidad'], ['Talco para pies', 'Frasco'], ['Gel antibacterial de bolsillo', 'Frasco'],
    ],
    'Bebé y maternidad' => [
        ['Pañal para bebé talla RN', 'Unidad'], ['Pañal para bebé talla P', 'Unidad'], ['Pañal para bebé talla M', 'Unidad'],
        ['Pañal para bebé talla G', 'Unidad'], ['Pañal para bebé talla XG', 'Unidad'], ['Pañal para bebé talla XXG', 'Unidad'],
        ['Toallitas húmedas para bebé', 'Unidad'], ['Crema para escaldaduras', 'Tubo'], ['Óleo calcáreo', 'Frasco'],
        ['Shampoo para bebé', 'Frasco'], ['Jabón para bebé', 'Unidad'], ['Colonia para bebé', 'Frasco'], ['Talco para bebé', 'Frasco'],
        ['Aceite para bebé', 'Frasco'], ['Biberón 4 oz', 'Unidad'], ['Biberón 9 oz', 'Unidad'], ['Chupón (tetina) de repuesto', 'Unidad'],
        ['Chupón de entretención', 'Unidad'], ['Mordedor', 'Unidad'], ['Termómetro para bebé', 'Unidad'], ['Aspirador nasal', 'Unidad'],
        ['Fórmula láctea etapa 1', 'Unidad'], ['Fórmula láctea etapa 2', 'Unidad'], ['Fórmula láctea etapa 3', 'Unidad'],
        ['Cereal infantil', 'Unidad'], ['Extractor de leche manual', 'Unidad'], ['Protectores de lactancia', 'Unidad'],
        ['Crema para pezones', 'Tubo'], ['Faja postparto', 'Unidad'], ['Pañal de tela', 'Unidad'],
    ],
    'Salud sexual' => [
        ['Preservativos (caja x 3)', 'Caja'], ['Preservativos (caja x 12)', 'Caja'], ['Gel lubricante íntimo', 'Tubo'],
        ['Prueba de ovulación', 'Unidad'],
    ],
    'Nutrición y bienestar' => [
        ['Sales de rehidratación oral (sobre)', 'Sobre'], ['Bebida rehidratante', 'Frasco'], ['Suplemento nutricional adulto', 'Unidad'],
        ['Suplemento nutricional infantil', 'Unidad'], ['Proteína en polvo', 'Unidad'], ['Colágeno hidrolizado', 'Unidad'],
        ['Avena para bebé', 'Unidad'], ['Miel de abeja', 'Frasco'], ['Infusión de manzanilla', 'Caja'], ['Infusión de anís', 'Caja'],
        ['Edulcorante', 'Unidad'], ['Caramelos para la garganta', 'Unidad'], ['Malteada nutricional para diabéticos', 'Unidad'],
    ],
];
