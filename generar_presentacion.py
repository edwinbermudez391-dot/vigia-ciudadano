# -*- coding: utf-8 -*-
"""
Genera la presentación de exposición de Vigía Ciudadano en formato .pptx.
Diseño 16:9, tema oscuro verde/menta, 15 diapositivas.
"""

from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR, MSO_AUTO_SIZE
from pptx.enum.shapes import MSO_SHAPE, MSO_CONNECTOR
from pptx.oxml.ns import qn
from lxml import etree
import os

# ---------------------------------------------------------------------------
# Sistema de diseño
# ---------------------------------------------------------------------------

BG        = RGBColor(0x06, 0x2E, 0x24)
BG_ALT    = RGBColor(0x08, 0x38, 0x2C)
CARD      = RGBColor(0x0C, 0x3F, 0x32)
CARD_HI   = RGBColor(0x10, 0x50, 0x3F)
BORDER    = RGBColor(0x1D, 0x5A, 0x48)
MINT      = RGBColor(0x34, 0xD3, 0x99)
MINT_DARK = RGBColor(0x08, 0x7B, 0x5F)
MINT_SOFT = RGBColor(0xA7, 0xF3, 0xD0)
AMBER     = RGBColor(0xFB, 0xBF, 0x24)
ROSE      = RGBColor(0xFB, 0x71, 0x85)
WHITE     = RGBColor(0xF8, 0xFA, 0xFC)
MUTED     = RGBColor(0x9D, 0xBD, 0xB1)

FONT = "Segoe UI"
MONO = "Consolas"

W, H = 13.333, 7.5
M = 0.75

prs = Presentation()
prs.slide_width = Inches(W)
prs.slide_height = Inches(H)
BLANK = prs.slide_layouts[6]

TOTAL = 15


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def new_slide(bg=BG):
    slide = prs.slides.add_slide(BLANK)
    rect = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, Inches(W), Inches(H))
    rect.fill.solid()
    rect.fill.fore_color.rgb = bg
    rect.line.fill.background()
    rect.shadow.inherit = False
    return slide


def set_alpha(shape, opacity):
    """opacity: 0-100"""
    solid = shape.fill._xPr.find(qn("a:solidFill"))
    if solid is None:
        return
    clr = solid.find(qn("a:srgbClr"))
    if clr is None:
        return
    alpha = etree.SubElement(clr, qn("a:alpha"))
    alpha.set("val", str(int(opacity * 1000)))


def circle(slide, x, y, d, color, opacity=100):
    shp = slide.shapes.add_shape(MSO_SHAPE.OVAL, Inches(x), Inches(y), Inches(d), Inches(d))
    shp.fill.solid()
    shp.fill.fore_color.rgb = color
    shp.line.fill.background()
    shp.shadow.inherit = False
    if opacity < 100:
        set_alpha(shp, opacity)
    return shp


def card(slide, x, y, w, h, fill=CARD, line=BORDER, radius=0.06, line_w=1.0):
    shp = slide.shapes.add_shape(
        MSO_SHAPE.ROUNDED_RECTANGLE, Inches(x), Inches(y), Inches(w), Inches(h)
    )
    shp.fill.solid()
    shp.fill.fore_color.rgb = fill
    if line is None:
        shp.line.fill.background()
    else:
        shp.line.color.rgb = line
        shp.line.width = Pt(line_w)
    shp.shadow.inherit = False
    try:
        shp.adjustments[0] = radius
    except Exception:
        pass
    return shp


def add_lines(slide, x, y, w, h, lines, align=PP_ALIGN.LEFT, anchor=MSO_ANCHOR.TOP):
    box = slide.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(h))
    tf = box.text_frame
    tf.word_wrap = True
    tf.auto_size = MSO_AUTO_SIZE.NONE
    tf.margin_left = 0
    tf.margin_right = 0
    tf.margin_top = 0
    tf.margin_bottom = 0
    tf.vertical_anchor = anchor

    for i, spec in enumerate(lines):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = spec.get("align", align)
        p.line_spacing = spec.get("spacing", 1.0)
        p.space_before = Pt(spec.get("space_before", 0))
        p.space_after = Pt(spec.get("space_after", 0))

        parts = spec.get("parts") or [(spec.get("text", ""), {})]
        for text, override in parts:
            run = p.add_run()
            run.text = text
            font = run.font
            font.name = override.get("font", spec.get("font", FONT))
            font.size = Pt(override.get("size", spec.get("size", 14)))
            font.bold = override.get("bold", spec.get("bold", False))
            font.italic = override.get("italic", spec.get("italic", False))
            font.color.rgb = override.get("color", spec.get("color", WHITE))
            spacing = override.get("char_spacing", spec.get("char_spacing"))
            if spacing:
                run.font._rPr.set("spc", str(int(spacing * 100)))

    return box


def add_text(slide, x, y, w, h, text, size=14, color=WHITE, bold=False,
             font=FONT, align=PP_ALIGN.LEFT, anchor=MSO_ANCHOR.TOP,
             spacing=1.0, space_after=0, char_spacing=None, italic=False):
    return add_lines(
        slide, x, y, w, h,
        [{
            "text": text, "size": size, "color": color, "bold": bold,
            "font": font, "spacing": spacing, "space_after": space_after,
            "char_spacing": char_spacing, "italic": italic, "align": align,
        }],
        align=align, anchor=anchor,
    )


def line(slide, x1, y1, x2, y2, color=BORDER, width=1.0, dash=None):
    conn = slide.shapes.add_connector(
        MSO_CONNECTOR.STRAIGHT, Inches(x1), Inches(y1), Inches(x2), Inches(y2)
    )
    conn.line.color.rgb = color
    conn.line.width = Pt(width)
    if dash:
        conn.line.dash_style = dash
    return conn


def chip(slide, x, y, w, h, label, fill=None, text_color=MINT_SOFT,
         border=None, size=10, bold=True, font=FONT):
    shp = card(slide, x, y, w, h, fill=fill or CARD, line=border, radius=0.5)
    tf = shp.text_frame
    tf.word_wrap = False
    tf.margin_left = Inches(0.08)
    tf.margin_right = Inches(0.08)
    tf.margin_top = 0
    tf.margin_bottom = 0
    tf.vertical_anchor = MSO_ANCHOR.MIDDLE
    p = tf.paragraphs[0]
    p.alignment = PP_ALIGN.CENTER
    r = p.add_run()
    r.text = label
    r.font.name = font
    r.font.size = Pt(size)
    r.font.bold = bold
    r.font.color.rgb = text_color
    return shp


def header(slide, kicker, title, subtitle=None, number=1):
    # marca lateral
    bar = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(M), Inches(0.62), Inches(0.055), Inches(0.9))
    bar.fill.solid()
    bar.fill.fore_color.rgb = MINT
    bar.line.fill.background()
    bar.shadow.inherit = False

    add_text(slide, M + 0.22, 0.6, 10.5, 0.3, kicker.upper(), size=10.5,
             color=MINT, bold=True, char_spacing=2.2)
    add_text(slide, M + 0.22, 0.88, 11.4, 0.7, title, size=31, color=WHITE, bold=True, spacing=0.95)
    if subtitle:
        add_text(slide, M + 0.22, 1.55, 11.3, 0.4, subtitle, size=13, color=MUTED, spacing=1.05)
    footer(slide, number)


def footer(slide, number):
    line(slide, M, 7.03, W - M, 7.03, color=BORDER, width=0.75)
    add_text(slide, M, 7.12, 8, 0.25, "Vigía Ciudadano  ·  Gestión de incidencias urbanas", size=9, color=MUTED)
    add_text(slide, W - M - 1.2, 7.12, 1.2, 0.25, f"{number:02d} / {TOTAL}", size=9,
             color=MUTED, align=PP_ALIGN.RIGHT)


def bullet_lines(items, size=13, color=WHITE, marker_color=MINT, gap=7):
    out = []
    for item in items:
        if isinstance(item, tuple):
            lead, rest = item
            out.append({
                "parts": [
                    ("●  ", {"size": 7, "color": marker_color, "bold": True}),
                    (lead, {"size": size, "color": color, "bold": True}),
                    (rest, {"size": size, "color": color}),
                ],
                "space_after": gap,
                "spacing": 1.08,
            })
        else:
            out.append({
                "parts": [
                    ("●  ", {"size": 7, "color": marker_color, "bold": True}),
                    (item, {"size": size, "color": color}),
                ],
                "space_after": gap,
                "spacing": 1.08,
            })
    return out


def section_label(slide, x, y, w, text, color=MINT):
    add_text(slide, x, y, w, 0.25, text.upper(), size=9.5, color=color, bold=True, char_spacing=1.8)


# ===========================================================================
# 01 — PORTADA
# ===========================================================================

s = new_slide()
circle(s, 9.1, -2.3, 6.2, MINT, 7)
circle(s, 11.4, 4.4, 3.6, AMBER, 5)
circle(s, -1.6, 5.4, 3.2, MINT, 4)

accent = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(M), Inches(1.15), Inches(0.06), Inches(3.9))
accent.fill.solid(); accent.fill.fore_color.rgb = MINT
accent.line.fill.background(); accent.shadow.inherit = False

add_text(s, M + 0.3, 1.12, 7.6, 0.3,
         "SISTEMA INTEGRAL DE GESTIÓN DE INCIDENCIAS URBANAS",
         size=11, color=MINT, bold=True, char_spacing=2.0)

add_lines(s, M + 0.3, 1.55, 7.8, 2.3, [
    {"text": "Vigía", "size": 62, "bold": True, "color": WHITE, "spacing": 0.9},
    {"text": "Ciudadano", "size": 62, "bold": True, "color": MINT, "spacing": 0.9},
])

add_text(s, M + 0.3, 3.85, 7.1, 0.9,
         "Del reporte ciudadano a la obra certificada: trazabilidad completa con evidencia y control de calidad.",
         size=16, color=MINT_SOFT, spacing=1.15)

stack = ["Laravel 12", "PHP 8.2+", "SQLite", "Tailwind 4"]
x = M + 0.3
for label in stack:
    w = 0.28 + len(label) * 0.095
    chip(s, x, 5.05, w, 0.36, label, fill=CARD, text_color=WHITE, border=BORDER, size=10.5)
    x += w + 0.15

add_text(s, M + 0.3, 6.35, 7, 0.3, "Documento de exposición  ·  Octubre 2026", size=11, color=MUTED)

# Tarjeta de expediente (mockup)
card(s, 9.15, 1.55, 3.45, 4.55, fill=CARD, line=BORDER, radius=0.05)
add_text(s, 9.42, 1.78, 2.9, 0.25, "EXPEDIENTE DIGITAL", size=8.5, color=MINT, bold=True, char_spacing=1.6)

ticket = card(s, 9.42, 2.12, 1.85, 0.34, fill=BG_ALT, line=BORDER, radius=0.25)
add_text(s, 9.42, 2.16, 1.85, 0.25, "REP-2026-0012", size=10, color=MINT_SOFT,
         bold=True, font=MONO, align=PP_ALIGN.CENTER)

add_text(s, 9.42, 2.62, 2.9, 0.6, "Bache en Av. Principal", size=15, color=WHITE, bold=True, spacing=1.0)
add_text(s, 9.42, 3.02, 2.9, 0.3, "Malla vial  ·  SLA 48 h", size=9.5, color=MUTED)
line(s, 9.42, 3.38, 12.32, 3.38, color=BORDER, width=0.75)

steps = [("Reportado", MINT, True), ("En ejecución", MINT, True),
         ("En inspección", AMBER, False), ("Cerrado", MUTED, False)]
sy = 3.58
for label, color, done in steps:
    dot = circle(s, 9.45, sy + 0.07, 0.14, color)
    if not done:
        dot.fill.background()
        dot.line.color.rgb = color
        dot.line.width = Pt(1.5)
    add_text(s, 9.72, sy, 2.4, 0.25, label, size=10.5,
             color=WHITE if done or color == AMBER else MUTED, bold=done)
    sy += 0.42

line(s, 9.42, 5.55, 12.32, 5.55, color=BORDER, width=0.75)
add_text(s, 9.42, 5.68, 2.9, 0.25, "Foto  ·  GPS  ·  Inspección", size=9, color=MUTED)

# ===========================================================================
# 02 — EL PROBLEMA
# ===========================================================================

s = new_slide()
header(s, "Punto de partida", "El problema que motiva el proyecto",
       "La gestión de incidencias urbanas suele perder el hilo entre el reporte y la solución.", 2)

problems = [
    ("01", "Reportes dispersos", "Teléfono, redes sociales y ventanilla: no existe un expediente único ni un responsable claro."),
    ("02", "Ciudadanía sin respuesta", "El ciudadano no sabe en qué va su solicitud ni cuándo será atendida."),
    ("03", "Obras sin evidencia", "La reparación se declara terminada sin foto, sin acta y sin verificación en el sitio."),
    ("04", "Sin control de calidad", "Nadie audita de forma independiente si la obra realmente cumple la especificación."),
]

positions = [(M, 2.18), (6.72, 2.18), (M, 3.92), (6.72, 3.92)]
for (num, title, desc), (x, y) in zip(problems, positions):
    card(s, x, y, 5.86, 1.52, fill=CARD, line=BORDER, radius=0.06)
    add_text(s, x + 0.28, y + 0.24, 0.7, 0.5, num, size=21, color=MINT, bold=True)
    add_text(s, x + 1.02, y + 0.22, 4.55, 0.3, title, size=15, color=WHITE, bold=True)
    add_text(s, x + 1.02, y + 0.62, 4.6, 0.75, desc, size=11.5, color=MUTED, spacing=1.08)

callout = card(s, M, 5.72, 11.83, 0.82, fill=CARD_HI, line=None, radius=0.06)
bar = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(M), Inches(5.72), Inches(0.07), Inches(0.82))
bar.fill.solid(); bar.fill.fore_color.rgb = AMBER
bar.line.fill.background(); bar.shadow.inherit = False
add_text(s, M + 0.3, 5.94, 11.2, 0.4,
         "Resultado: reportes sin dueño, obras sin verificar y ciudadanía sin respuesta.",
         size=15, color=WHITE, bold=True)

# ===========================================================================
# 03 — QUÉ ES
# ===========================================================================

s = new_slide()
header(s, "La propuesta", "¿Qué es Vigía Ciudadano?",
       "Una sola plataforma para que el reporte ciudadano se convierta en una obra verificada.", 3)

card(s, M, 2.05, 11.83, 1.42, fill=CARD, line=BORDER, radius=0.06)
add_text(s, M + 0.35, 2.28, 11.15, 1.0,
         "Aplicación web que gestiona el ciclo de vida completo de una incidencia urbana: "
         "radicación ciudadana, despacho a contratistas, ejecución con evidencia e inspección "
         "independiente de calidad antes del cierre.",
         size=15.5, color=WHITE, spacing=1.15)

section_label(s, M, 3.82, 6, "Un expediente, cuatro actores")

flow = [
    ("01", "Ciudadano", "Reporta con foto y GPS"),
    ("02", "Alcaldía", "Despacha y controla SLA"),
    ("03", "Contratista", "Ejecuta y entrega evidencia"),
    ("04", "Inspector", "Audita y dictamina"),
    ("05", "Cierre", "Incidencia certificada"),
]
fw, gap = 2.05, 0.4
x = M
for num, title, desc in flow:
    fill = CARD_HI if num == "05" else CARD
    border = MINT_DARK if num == "05" else BORDER
    card(s, x, 4.18, fw, 1.4, fill=fill, line=border, radius=0.07)
    add_text(s, x + 0.18, 4.3, 1.7, 0.22, num, size=9, color=MINT, bold=True, char_spacing=1.5)
    add_text(s, x + 0.18, 4.56, fw - 0.36, 0.3, title, size=13.5, color=WHITE, bold=True)
    add_text(s, x + 0.18, 4.92, fw - 0.36, 0.65, desc, size=10, color=MUTED, spacing=1.05)
    if x + fw + 0.12 < W - M:
        add_text(s, x + fw + 0.04, 4.78, 0.32, 0.35, "›", size=22, color=MINT, bold=True,
                 align=PP_ALIGN.CENTER)
    x += fw + gap

add_text(s, M, 6.0, 11.8, 0.55,
         "Cada acción queda registrada con evidencia fotográfica, coordenadas GPS y marcas de tiempo: "
         "la gestión deja de ser informal y se vuelve transparente y auditable.",
         size=11.5, color=MUTED, spacing=1.1)

# ===========================================================================
# 04 — OBJETIVOS
# ===========================================================================

s = new_slide()
header(s, "Dirección", "Objetivo del proyecto", None, 4)

card(s, M, 1.95, 4.65, 4.55, fill=CARD_HI, line=MINT_DARK, radius=0.05)
add_text(s, M + 0.35, 2.18, 3.95, 0.3, "OBJETIVO GENERAL", size=10, color=MINT, bold=True, char_spacing=1.8)
add_text(s, M + 0.35, 2.55, 3.95, 1.7,
         "Radicar, asignar, ejecutar y auditar incidencias de infraestructura pública con trazabilidad de punta a punta y control real de calidad.",
         size=15.5, color=WHITE, bold=True, spacing=1.16)
line(s, M + 0.35, 4.42, M + 4.3, 4.42, color=BORDER, width=0.75)
add_text(s, M + 0.35, 4.58, 3.95, 1.4,
         "Un mismo expediente conecta a ciudadanía, alcaldía, contratistas e inspectores, sin perder evidencia ni estado en el camino.",
         size=11, color=MUTED, spacing=1.1)

specifics = [
    ("Radicación pública", "Reportar sin cuenta, con foto, GPS y ticket único."),
    ("Despacho con SLA", "Asignar cuadrillas y vigilar tiempos por categoría."),
    ("Agenda del contratista", "Iniciar y finalizar obras con evidencia."),
    ("Inspección de calidad", "Aprobar o devolver la obra a reproceso."),
    ("Notificaciones", "Avisar a suscriptores en cada cambio de estado."),
    ("API REST", "Operar el mismo flujo desde clientes móviles."),
]
y = 1.95
for i, (title, desc) in enumerate(specifics):
    num = f"{i+1:02d}"
    add_text(s, 5.72, y + 0.02, 0.45, 0.3, num, size=12, color=MINT, bold=True, font=MONO)
    add_text(s, 6.18, y, 2.35, 0.3, title, size=12.5, color=WHITE, bold=True)
    add_text(s, 8.62, y + 0.01, 3.9, 0.55, desc, size=11, color=MUTED, spacing=1.05)
    if i < len(specifics) - 1:
        line(s, 5.72, y + 0.62, 12.55, y + 0.62, color=BORDER, width=0.5)
    y += 0.76

# ===========================================================================
# 05 — ACTORES
# ===========================================================================

s = new_slide()
header(s, "Quién hace qué", "Cuatro actores, un solo expediente",
       "Roles separados que se controlan entre sí: quien ejecuta no es quien certifica.", 5)

roles = [
    ("CIUDADANÍA", "Reporta y consulta", MINT,
     ["Radica sin crear cuenta", "Adjunta foto y GPS", "Sigue el ticket en línea", "Se suscribe a novedades"],
     "Sin cuenta · ticket único"),
    ("ALCALDÍA", "Despacha y controla", MINT_SOFT,
     ["Dashboard operativo", "Asigna o reasigna obras", "Gestiona cuadrillas", "Vigila SLA por categoría"],
     "Despacho · control de SLA"),
    ("CONTRATISTA", "Ejecuta la obra", AMBER,
     ["Ve su agenda asignada", "Inicia la ejecución", "Registra acta del trabajo", "Adjunta foto de la solución"],
     "Evidencia obligatoria"),
    ("INSPECTOR", "Audita y certifica", ROSE,
     ["Cola FIFO de inspecciones", "Compara daño vs. solución", "Checklist técnico en sitio", "Aprueba o envía a reproceso"],
     "Dictamen en sitio · GPS"),
]
cw, gap = 2.78, 0.24
x = M
for role, sub, accent, items, control in roles:
    card(s, x, 2.05, cw, 3.95, fill=CARD, line=BORDER, radius=0.05)
    top = s.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(x), Inches(2.05), Inches(cw), Inches(0.09))
    top.fill.solid(); top.fill.fore_color.rgb = accent
    top.line.fill.background(); top.shadow.inherit = False

    add_text(s, x + 0.24, 2.32, cw - 0.48, 0.28, role, size=12, color=accent, bold=True, char_spacing=1.6)
    add_text(s, x + 0.24, 2.62, cw - 0.48, 0.3, sub, size=14, color=WHITE, bold=True)
    line(s, x + 0.24, 3.08, x + cw - 0.24, 3.08, color=BORDER, width=0.6)
    add_lines(s, x + 0.24, 3.24, cw - 0.44, 2.0,
              bullet_lines(items, size=10.5, gap=8, marker_color=accent))
    line(s, x + 0.24, 5.28, x + cw - 0.24, 5.28, color=BORDER, width=0.6)
    add_text(s, x + 0.24, 5.42, cw - 0.44, 0.4, control, size=9, color=MUTED, italic=True)
    x += cw + gap

add_text(s, M, 6.3, 11.8, 0.45,
         "El contratista no puede aprobar su propia obra y el inspector no puede ejecutarla: la separación de roles es el corazón del control.",
         size=11.5, color=MUTED, align=PP_ALIGN.CENTER)

# ===========================================================================
# 06 — CICLO DE VIDA
# ===========================================================================

s = new_slide()
header(s, "Flujo operativo", "El ciclo de vida de una incidencia",
       "Cinco estados explícitos, con retorno controlado cuando la calidad no se cumple.", 6)

stages = [
    ("reported", "Reportado", "El ciudadano radica\nla incidencia.", MINT),
    ("in_progress", "En ejecución", "La alcaldía asigna y\nel contratista trabaja.", MINT),
    ("under_inspection", "En inspección", "La obra termina y\npasa a auditoría.", AMBER),
    ("closed", "Cerrado", "El inspector aprueba\ny se cierra.", MINT),
]

xs = [1.15, 4.05, 6.95, 9.85]
cy = 3.05
line(s, xs[0] + 0.62, cy + 0.31, xs[-1] + 0.62, cy + 0.31, color=BORDER, width=2.0)

for (code, title, desc, color), x in zip(stages, xs):
    circle(s, x, cy, 0.62, color)
    add_text(s, x - 0.1, cy + 0.14, 0.82, 0.3, "✓" if color == MINT else "◔", size=13,
             color=BG, bold=True, align=PP_ALIGN.CENTER)
    add_text(s, x - 0.35, cy - 0.45, 1.35, 0.3, code, size=9, color=color, bold=True,
             font=MONO, align=PP_ALIGN.CENTER)
    add_text(s, x - 0.55, cy + 0.82, 1.75, 0.3, title, size=14, color=WHITE, bold=True,
             align=PP_ALIGN.CENTER)
    add_text(s, x - 0.7, cy + 1.18, 2.05, 0.75, desc, size=10.5, color=MUTED,
             align=PP_ALIGN.CENTER, spacing=1.1)

# Bucle de reproceso: banda inferior, sin líneas que crucen el contenido
band = card(s, M, 5.3, 11.83, 0.8, fill=CARD, line=ROSE, radius=0.08, line_w=1.25)
add_text(s, M + 0.25, 5.37, 0.6, 0.55, "↺", size=26, color=ROSE, bold=True, align=PP_ALIGN.CENTER)
add_text(s, M + 0.95, 5.42, 4.3, 0.25, "REWORK  ·  REPROCESO", size=9.5, color=ROSE,
         bold=True, char_spacing=1.4)
add_text(s, M + 0.95, 5.69, 10.6, 0.3,
         "La orden vuelve a in_progress y el contratista corrige; el expediente no se duplica.",
         size=10.5, color=WHITE)

add_text(s, M, 6.35, 11.8, 0.3,
         "El cierre no es automático: exige un dictamen de calidad con evidencia.",
         size=11, color=MUTED, align=PP_ALIGN.CENTER)

# ===========================================================================
# 07 — ARQUITECTURA
# ===========================================================================

s = new_slide()
header(s, "Cómo está construido", "Arquitectura y stack tecnológico",
       "Un monolito modular donde la web y la API comparten las mismas reglas de negocio.", 7)

layers = [
    ("Interfaz web", "Blade SSR + Tailwind 4 + JavaScript", MINT),
    ("API REST v1", "Sanctum: cookie de sesión y tokens Bearer", MINT_SOFT),
    ("Servicios de dominio", "Reglas de negocio compartidas por ambos canales", AMBER),
    ("Datos y evidencia", "SQLite · fotos en disco local o S3", ROSE),
]
y = 2.0
for name, desc, accent in layers:
    card(s, M, y, 6.55, 0.98, fill=CARD, line=BORDER, radius=0.06)
    bar = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(M), Inches(y), Inches(0.06), Inches(0.98))
    bar.fill.solid(); bar.fill.fore_color.rgb = accent
    bar.line.fill.background(); bar.shadow.inherit = False
    add_text(s, M + 0.28, y + 0.16, 2.2, 0.3, name, size=13.5, color=WHITE, bold=True)
    add_text(s, M + 0.28, y + 0.52, 5.95, 0.35, desc, size=10.5, color=MUTED)
    y += 1.12

section_label(s, 7.7, 1.95, 4.5, "Stack")
stack = [
    ("Laravel 12", "Backend"), ("PHP 8.2+", "Lenguaje"),
    ("SQLite", "Base de datos"), ("Sanctum", "Autenticación"),
    ("Tailwind 4", "Estilos"), ("PHPUnit 11", "Pruebas"),
]
for i, (name, tag) in enumerate(stack):
    col, row = i % 2, i // 2
    x = 7.7 + col * 2.28
    yy = 2.3 + row * 0.72
    card(s, x, yy, 2.12, 0.6, fill=CARD, line=BORDER, radius=0.1)
    add_text(s, x + 0.16, yy + 0.1, 1.85, 0.22, name, size=11.5, color=WHITE, bold=True)
    add_text(s, x + 0.16, yy + 0.33, 1.85, 0.2, tag, size=8.5, color=MUTED)

card(s, 7.7, 4.72, 4.88, 1.75, fill=CARD_HI, line=MINT_DARK, radius=0.06)
add_text(s, 7.92, 4.9, 4.45, 0.25, "SERVICIOS CLAVE", size=9.5, color=MINT, bold=True, char_spacing=1.6)
services = [
    "ReportIntake · radicación y ticket único",
    "WorkOrderExecution · assigned → in_execution → completed",
    "QualityInspectionRecorder · cierre atómico del ciclo",
    "ImageUploader · evidencia con compensación",
]
add_lines(s, 7.92, 5.2, 4.45, 1.1,
          [{"text": srv, "size": 9.5, "color": WHITE, "space_after": 4, "spacing": 1.0}
           for srv in services])

# ===========================================================================
# 08 — SEPARADOR DE SECCIÓN
# ===========================================================================

s = new_slide()
circle(s, 9.6, -1.8, 5.8, MINT, 8)
circle(s, -1.2, 4.9, 3.4, AMBER, 5)

chip(s, M, 1.55, 3.35, 0.4, "FUNCIONALIDAD AL 100%", fill=CARD_HI, text_color=MINT,
     border=MINT_DARK, size=10.5)

add_text(s, M, 2.2, 10.5, 0.5, "El cierre del ciclo", size=20, color=MINT_SOFT, bold=False)
add_text(s, M, 2.68, 11.3, 1.5, "Inspección de calidad\nde obra", size=48, color=WHITE,
         bold=True, spacing=0.95)

add_text(s, M, 4.35, 8.6, 0.85,
         "La única funcionalidad completa de extremo a extremo: interfaz, API, reglas, "
         "notificaciones y pruebas automatizadas.",
         size=15, color=MUTED, spacing=1.15)

checks = ["Interfaz web", "API REST", "Validaciones", "Transición atómica", "Notificaciones", "24 pruebas"]
x = M
for label in checks:
    w = 0.34 + len(label) * 0.098
    chip(s, x, 5.55, w, 0.4, label, fill=CARD, text_color=MINT_SOFT, border=BORDER, size=10)
    x += w + 0.14

# ===========================================================================
# 09 — CÓMO FUNCIONA
# ===========================================================================

s = new_slide()
header(s, "Paso a paso", "Cómo funciona la inspección",
       "De la obra terminada al dictamen firmado en el sitio, sin saltos ni decisiones informales.", 9)

steps = [
    ("01", "La obra termina", "El contratista registra acta y foto. El reporte pasa a under_inspection."),
    ("02", "Cola de inspección", "El inspector ve las obras pendientes ordenadas por antigüedad (FIFO)."),
    ("03", "Comparar evidencia", "Foto del daño original frente a la solución entregada por el contratista."),
    ("04", "Checklist técnico", "Cinco puntos obligatorios: cumple o no cumple, con botones táctiles."),
    ("05", "Foto y GPS", "Evidencia de verificación y posición real del inspector en el sitio."),
    ("06", "Emitir dictamen", "Aprobar cierra la incidencia; reproceso la devuelve a ejecución."),
]
cw, ch, gapx, gapy = 3.74, 1.72, 0.3, 0.22
for i, (num, title, desc) in enumerate(steps):
    col, row = i % 3, i // 3
    x = M + col * (cw + gapx)
    y = 2.0 + row * (ch + gapy)
    card(s, x, y, cw, ch, fill=CARD, line=BORDER, radius=0.06)
    add_text(s, x + 0.24, y + 0.2, 0.6, 0.3, num, size=12, color=MINT, bold=True, font=MONO)
    add_text(s, x + 0.82, y + 0.19, cw - 1.0, 0.3, title, size=14, color=WHITE, bold=True)
    add_text(s, x + 0.24, y + 0.72, cw - 0.48, 0.75, desc, size=10.5, color=MUTED, spacing=1.1)

add_text(s, M, 5.98, 11.8, 0.35,
         "La decisión se toma con la evidencia a la vista, no desde un escritorio.",
         size=11.5, color=MUTED, align=PP_ALIGN.CENTER, italic=True)

# ===========================================================================
# 10 — PANTALLA DE AUDITORÍA
# ===========================================================================

s = new_slide()
header(s, "La interfaz", "La pantalla de auditoría",
       "Todo lo que el inspector necesita para decidir, en una sola vista táctil.", 10)

# Mockup
card(s, M, 2.0, 7.35, 4.55, fill=BG_ALT, line=BORDER, radius=0.04)

hdr = card(s, M + 0.22, 2.18, 6.9, 0.52, fill=CARD, line=None, radius=0.12)
add_text(s, M + 0.38, 2.28, 1.55, 0.3, "REP-2026-0007", size=9.5, color=MINT_SOFT, bold=True, font=MONO)
add_text(s, M + 2.05, 2.28, 3.2, 0.3, "Andén hundido con baldosas rotas", size=10.5, color=WHITE, bold=True)
chip(s, M + 5.75, 2.27, 1.2, 0.32, "En inspección", fill=BG, text_color=AMBER, border=AMBER, size=8.5)

# Fotos
for i, (label, color) in enumerate([("Daño reportado", MUTED), ("Solución entregada", MUTED)]):
    fx = M + 0.22 + i * 3.48
    ph = card(s, fx, 2.85, 3.38, 1.42, fill=CARD, line=BORDER, radius=0.05)
    add_text(s, fx + 0.15, 3.3, 3.08, 0.3, "▧  FOTO", size=10, color=MUTED, align=PP_ALIGN.CENTER, bold=True)
    add_text(s, fx + 0.15, 3.98, 3.08, 0.2, label, size=8.5, color=MUTED, align=PP_ALIGN.CENTER)

# Checklist
cl = [("Superficie uniforme", "Cumple", MINT), ("Pendiente de drenaje", "Cumple", MINT),
      ("Materiales especificados", "No cumple", ROSE)]
cy = 4.42
for label, answer, color in cl:
    add_text(s, M + 0.25, cy + 0.04, 3.3, 0.25, label, size=9, color=WHITE)
    chip(s, M + 3.6, cy, 1.0, 0.28, "✓ Cumple" if color == MINT else "✗ No cumple",
         fill=CARD, text_color=color, border=color, size=7.5)
    cy += 0.34

# Botones
chip(s, M + 0.25, 5.55, 2.7, 0.48, "APROBAR", fill=MINT_DARK, text_color=WHITE, border=None, size=11)
chip(s, M + 3.1, 5.55, 2.7, 0.48, "REPROCESO", fill=ROSE, text_color=WHITE, border=None, size=11)
add_text(s, M + 5.95, 5.62, 1.2, 0.3, "GPS ✓", size=9, color=MINT, bold=True, align=PP_ALIGN.CENTER)

# Features derecha
feats = [
    ("Comparativa visual", "Daño original vs. solución, lado a lado."),
    ("Checklist táctil", "Botones grandes para usar en campo."),
    ("Evidencia obligatoria", "Foto de verificación y GPS sin excepción."),
    ("Doble validación", "JavaScript en cliente y Form Request en servidor."),
]
y = 2.0
for title, desc in feats:
    card(s, 8.45, y, 4.13, 0.98, fill=CARD, line=BORDER, radius=0.06)
    add_text(s, 8.65, y + 0.14, 3.75, 0.28, title, size=12, color=WHITE, bold=True)
    add_text(s, 8.65, y + 0.46, 3.75, 0.42, desc, size=9.5, color=MUTED, spacing=1.05)
    y += 1.12

# ===========================================================================
# 11 — REGLAS Y VALIDACIONES
# ===========================================================================

s = new_slide()
header(s, "Qué garantiza", "Reglas de negocio y validaciones",
       "La funcionalidad no depende de la buena voluntad: el servidor impone las reglas.", 11)

card(s, M, 2.0, 5.75, 4.55, fill=CARD, line=BORDER, radius=0.05)
add_text(s, M + 0.3, 2.2, 5.1, 0.3, "REGLAS DE NEGOCIO", size=10, color=MINT, bold=True, char_spacing=1.6)
rules = [
    ("Solo órdenes completed", " y reportes under_inspection."),
    ("Aprobado → closed", ": la incidencia queda cerrada."),
    ("Rework → in_execution", ": la orden vuelve al contratista."),
    ("Aprobado no admite motivo", " de rechazo; se normaliza a null."),
    ("Checklist de 1 a 50 ítems", ", cada respuesta escalar simple."),
    ("Solo inspectores activos", " con rol quality_inspector (doble capa)."),
    ("Evidencia única por visita", ": sufijo temporal en cada foto."),
    ("Trazabilidad por visita", ": foto, checklist y GPS por dictamen."),
    ("Compensación de archivos", " si falla la transacción."),
]
add_lines(s, M + 0.3, 2.58, 5.15, 3.85, bullet_lines(rules, size=10.5, gap=6))

card(s, 6.7, 2.0, 5.88, 4.55, fill=CARD, line=BORDER, radius=0.05)
add_text(s, 7.0, 2.2, 5.2, 0.3, "VALIDACIONES DEL SERVIDOR", size=10, color=AMBER, bold=True, char_spacing=1.6)

rows = [
    ("decision", "approved | rework"),
    ("checklist_results", "1–50 ítems escalares"),
    ("rejection_reason", "20–1000 caracteres si hay rework"),
    ("inspector_latitude", "numérico · -90 a 90"),
    ("inspector_longitude", "numérico · -180 a 180"),
    ("verification_photo", "JPEG/PNG/WebP · 5 MB · 4000 px"),
    ("work_order", "completed + reporte under_inspection"),
]
ry = 2.58
for i, (field, rule) in enumerate(rows):
    if i % 2 == 0:
        bg = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(7.0), Inches(ry - 0.04), Inches(5.28), Inches(0.48))
        bg.fill.solid(); bg.fill.fore_color.rgb = CARD_HI
        bg.line.fill.background(); bg.shadow.inherit = False
    add_text(s, 7.12, ry + 0.02, 2.15, 0.25, field, size=9, color=MINT_SOFT, bold=True, font=MONO)
    add_text(s, 9.32, ry + 0.02, 2.9, 0.3, rule, size=9, color=WHITE)
    ry += 0.5

add_text(s, 7.0, 6.18, 5.25, 0.35,
         "Mensajes de error en español que explican exactamente qué corregir.",
         size=9.5, color=MUTED, italic=True)

# ===========================================================================
# 12 — CONSISTENCIA Y NOTIFICACIONES
# ===========================================================================

s = new_slide()
header(s, "Confiabilidad", "Consistencia y notificaciones",
       "La inspección, el reporte y la orden cambian juntos; el ciudadano se entera a tiempo.", 12)

# Diagrama transacción
card(s, M, 2.0, 5.95, 4.55, fill=CARD, line=BORDER, radius=0.05)
add_text(s, M + 0.3, 2.2, 5.2, 0.3, "DB::TRANSACTION", size=10, color=MINT, bold=True, char_spacing=1.8)
add_text(s, M + 0.3, 2.5, 5.3, 0.35, "Una sola operación atómica", size=14, color=WHITE, bold=True)

boxes = [
    ("quality_inspections", "Se guarda el acta, checklist y foto.", MINT),
    ("reports.status", "Cambia a closed o rework.", MINT_SOFT),
    ("work_orders.status", "Se mantiene o vuelve a in_execution.", AMBER),
]
by = 3.08
for name, desc, accent in boxes:
    card(s, M + 0.3, by, 5.3, 0.72, fill=CARD_HI, line=BORDER, radius=0.08)
    add_text(s, M + 0.48, by + 0.1, 2.0, 0.25, name, size=9, color=accent, bold=True, font=MONO)
    add_text(s, M + 0.48, by + 0.36, 4.95, 0.25, desc, size=9.5, color=WHITE)
    by += 0.84

add_text(s, M + 0.3, 5.72, 5.3, 0.7,
         "Si algo falla, no queda una inspección sin transición ni una foto huérfana: "
         "la imagen se elimina y la operación se revierte.",
         size=10, color=MUTED, spacing=1.08)

# Notificaciones
notifs = [
    ("Aprobación", "Suscriptores y correo del ciudadano reciben el aviso de cierre.", MINT),
    ("Reproceso", "El aviso incluye el motivo del rechazo escrito por el inspector.", ROSE),
    ("Reporte anónimo", "Si dejó citizen_email, también recibe la notificación.", AMBER),
]
y = 2.0
for title, desc, accent in notifs:
    card(s, 7.0, y, 5.58, 1.25, fill=CARD, line=BORDER, radius=0.06)
    dot = circle(s, 7.24, y + 0.24, 0.12, accent)
    add_text(s, 7.48, y + 0.16, 4.8, 0.3, title, size=13, color=WHITE, bold=True)
    add_text(s, 7.48, y + 0.52, 4.85, 0.6, desc, size=10, color=MUTED, spacing=1.08)
    y += 1.4

card(s, 7.0, 6.1, 5.58, 0.45, fill=CARD_HI, line=None, radius=0.2)
add_text(s, 7.0, 6.19, 5.58, 0.25, "Notificaciones encoladas · after_commit", size=9.5,
         color=MINT_SOFT, align=PP_ALIGN.CENTER, bold=True)

# ===========================================================================
# 13 — EVIDENCIA 100%
# ===========================================================================

s = new_slide()
header(s, "Demostración", "¿Por qué está al 100%?",
       "No es una apreciación: la suite de pruebas cubre los dos caminos y las restricciones.", 13)

stats = [("24", "pruebas aprobadas"), ("95", "aserciones"), ("2", "caminos del negocio"), ("0", "tareas pendientes")]
sw = 2.8
x = M
for value, label in stats:
    card(s, x, 1.98, sw, 1.15, fill=CARD, line=BORDER, radius=0.06)
    add_text(s, x + 0.25, 2.08, 1.2, 0.55, value, size=30, color=MINT, bold=True)
    add_text(s, x + 1.18, 2.25, sw - 1.3, 0.55, label, size=9.5, color=MUTED, spacing=1.0)
    x += sw + 0.18

card(s, M, 3.35, 7.55, 3.2, fill=CARD, line=BORDER, radius=0.05)
add_text(s, M + 0.3, 3.52, 6.9, 0.3, "PRUEBAS QUE LO DEMUESTRAN", size=9.5, color=MINT, bold=True, char_spacing=1.6)

tests = [
    ("inspeccion_aprobada_cierra_la_incidencia", "closed + completed"),
    ("inspeccion_reprobada_devuelve_la_orden_a_ejecucion", "rework + in_execution"),
    ("no_se_inspecciona_una_orden_sin_terminar", "responde 422"),
    ("cierre y reproceso notifican a suscriptores", "correo + motivo"),
    ("consola del inspector protegida por rol", "auth + role"),
]
ty = 3.9
for name, result in tests:
    add_text(s, M + 0.3, ty, 4.55, 0.28, name, size=8.5, color=MINT_SOFT, font=MONO)
    add_text(s, 5.6, ty, 2.55, 0.28, result, size=9, color=WHITE, bold=True)
    ty += 0.46

card(s, 8.55, 3.35, 4.03, 3.2, fill=CARD_HI, line=MINT_DARK, radius=0.05)
add_text(s, 8.8, 3.55, 3.55, 0.3, "COBERTURA COMPLETA", size=9.5, color=MINT, bold=True, char_spacing=1.6)
cover = [
    "Aprobación y cierre",
    "Reproceso y reapertura",
    "Bloqueo de órdenes sin terminar",
    "Doble capa de rol",
    "Conservación de evidencia",
    "Compensación ante fallos",
]
add_lines(s, 8.8, 3.95, 3.55, 2.35, bullet_lines(cover, size=10, gap=8))

add_text(s, M + 0.3, 6.12, 6.9, 0.3,
         "Ejecutado con php artisan test  ·  24 passed (95 assertions)",
         size=9.5, color=MUTED, font=MONO)

# ===========================================================================
# 14 — CÓMO PROBARLA
# ===========================================================================

s = new_slide()
header(s, "Para la demostración", "Cómo probarla en vivo",
       "Cinco pasos y credenciales listas: el seeder deja obras pendientes de dictamen.", 14)

steps = [
    ("Preparar datos", "php artisan migrate:fresh --seed"),
    ("Levantar el sistema", "php artisan serve  +  npm run dev"),
    ("Iniciar sesión", "inspector1@calidad.gov.co"),
    ("Dictaminar", "Checklist + foto + GPS + decisión"),
    ("Verificar", "Estado del ticket: closed o rework"),
]
y = 2.0
for i, (title, detail) in enumerate(steps):
    card(s, M, y, 7.25, 0.8, fill=CARD, line=BORDER, radius=0.08)
    circle(s, M + 0.22, y + 0.2, 0.4, MINT_DARK)
    add_text(s, M + 0.22, y + 0.28, 0.4, 0.25, str(i + 1), size=10, color=WHITE, bold=True,
             align=PP_ALIGN.CENTER)
    add_text(s, M + 0.8, y + 0.12, 2.1, 0.25, title, size=12, color=WHITE, bold=True)
    add_text(s, M + 0.8, y + 0.43, 6.1, 0.28, detail, size=9.5,
             color=MINT_SOFT if i < 2 else MUTED, font=MONO if i < 2 else FONT)
    y += 0.94

card(s, 8.45, 2.0, 4.13, 2.55, fill=CARD_HI, line=MINT_DARK, radius=0.05)
add_text(s, 8.7, 2.18, 3.6, 0.25, "CREDENCIALES", size=9.5, color=MINT, bold=True, char_spacing=1.6)
creds = [
    ("Inspector 1", "inspector1@calidad.gov.co"),
    ("Inspector 2", "inspector2@calidad.gov.co"),
]
yy = 2.5
for label, email in creds:
    add_text(s, 8.7, yy, 3.6, 0.2, label, size=8.5, color=MUTED)
    add_text(s, 8.7, yy + 0.2, 3.6, 0.25, email, size=9.5, color=WHITE, bold=True, font=MONO)
    yy += 0.58

add_text(s, 8.7, 3.72, 3.6, 0.2, "Contraseña", size=8.5, color=MUTED)
chip(s, 8.7, 3.95, 1.35, 0.36, "password", fill=BG, text_color=AMBER, border=AMBER, size=10, font=MONO)

card(s, 8.45, 4.78, 4.13, 1.7, fill=CARD, line=BORDER, radius=0.05)
add_text(s, 8.7, 4.94, 3.6, 0.25, "RUTA", size=9.5, color=MINT, bold=True, char_spacing=1.6)
add_text(s, 8.7, 5.22, 3.6, 0.5, "/inspector/inspecciones", size=11, color=WHITE, bold=True, font=MONO)
add_text(s, 8.7, 5.72, 3.6, 0.6, "Elige una obra pendiente y abre la auditoría.", size=9.5, color=MUTED, spacing=1.05)

# ===========================================================================
# 15 — CIERRE
# ===========================================================================

s = new_slide()
circle(s, 8.9, -2.0, 6.4, MINT, 8)
circle(s, -1.4, 5.2, 3.0, AMBER, 5)

add_text(s, M, 1.35, 4.0, 0.3, "CONCLUSIÓN", size=10.5, color=MINT, bold=True, char_spacing=2.2)

add_lines(s, M, 1.85, 11.2, 2.4, [
    {"text": "Un reporte no termina", "size": 36, "color": WHITE, "bold": True, "spacing": 0.95},
    {"text": "cuando se tapa el hueco.", "size": 36, "color": MUTED, "bold": True, "spacing": 0.95},
    {"text": "Termina cuando la obra se verifica.", "size": 36, "color": MINT, "bold": True,
     "spacing": 0.95, "space_before": 4},
])

line(s, M, 4.35, M + 4.2, 4.35, color=MINT, width=2.5)

takeaways = [
    ("Trazabilidad", "Un expediente único de punta a punta."),
    ("Control real", "Quien ejecuta no certifica la obra."),
    ("Evidencia", "Foto, GPS y checklist en cada dictamen."),
]
x = M
for title, desc in takeaways:
    card(s, x, 4.65, 3.7, 1.15, fill=CARD, line=BORDER, radius=0.06)
    add_text(s, x + 0.24, 4.8, 3.25, 0.28, title, size=12.5, color=WHITE, bold=True)
    add_text(s, x + 0.24, 5.12, 3.25, 0.5, desc, size=9.5, color=MUTED, spacing=1.05)
    x += 3.9

add_text(s, M, 6.25, 7.0, 0.4, "Gracias.", size=26, color=WHITE, bold=True)
add_text(s, 8.2, 6.38, 4.4, 0.3, "Vigía Ciudadano  ·  Octubre 2026", size=11, color=MUTED,
         align=PP_ALIGN.RIGHT)

# ---------------------------------------------------------------------------
# Notas del expositor
# ---------------------------------------------------------------------------

notes = [
    "Abrir con la idea central: Vigía Ciudadano convierte un reporte ciudadano en una obra verificada. Aclarar que la presentación se basa en el sistema real y en su suite de pruebas.",
    "Plantear los cuatro dolores: reportes dispersos, ciudadanía sin respuesta, obras sin evidencia y ausencia de control de calidad. Cerrar con la frase de la banda inferior.",
    "Definir el sistema en una frase y recorrer el flujo de un vistazo: ciudadano, alcaldía, contratista, inspector y cierre. Enfatizar que es un solo expediente.",
    "Presentar el objetivo general y los seis específicos. Destacar que la API permite reutilizar el mismo flujo desde un cliente móvil sin duplicar reglas.",
    "Explicar los cuatro roles y la separación de poderes: quien ejecuta no certifica. Mencionar que cada rol tiene su propia superficie de trabajo.",
    "Recorrer los estados reported, in_progress, under_inspection y closed. Detenerse en rework: la orden vuelve a ejecución sin crear un expediente nuevo.",
    "Mostrar la arquitectura: interfaz web y API comparten los servicios de dominio. Mencionar los cuatro servicios clave y el stack.",
    "Cambio de sección: aquí comienza la funcionalidad terminada al 100%. Anticipar que se verán interfaz, API, reglas, atomicidad y pruebas.",
    "Recorrer los seis pasos del flujo de inspección. Hacer énfasis en que la decisión se toma con la evidencia a la vista y en el sitio.",
    "Describir la pantalla: comparativa de fotos, checklist táctil, panel de reproceso, foto de verificación, GPS y botones de dictamen.",
    "Explicar las reglas de negocio y las validaciones del servidor. Señalar la doble capa: JavaScript en el cliente y Form Request en el backend.",
    "Explicar la transacción atómica: inspección, reporte y orden cambian juntos. Si falla, se revierte y se elimina la foto. Luego las notificaciones.",
    "Presentar la evidencia: 24 pruebas y 95 aserciones. Nombrar los dos caminos (aprobación y reproceso) y las restricciones cubiertas.",
    "Hacer la demostración en vivo con estos pasos y credenciales. Si no hay entorno, mostrar el flujo en la diapositiva anterior.",
    "Cerrar con la frase: un reporte no termina cuando se tapa el hueco, sino cuando la obra se verifica. Agradecer y abrir preguntas.",
]

for slide, note in zip(prs.slides, notes):
    slide.notes_slide.notes_text_frame.text = note

# ---------------------------------------------------------------------------
# Guardar
# ---------------------------------------------------------------------------

output = os.path.join(os.path.dirname(os.path.abspath(__file__)), "Presentacion_Vigia_Ciudadano.pptx")
prs.save(output)
print(f"Presentación creada: {output}")
print(f"Diapositivas: {len(prs.slides)}")
print(f"Tamaño: {os.path.getsize(output):,} bytes")
