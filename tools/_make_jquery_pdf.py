# -*- coding: utf-8 -*-
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm, cm
from reportlab.lib.colors import HexColor, white, black
from reportlab.lib.enums import TA_LEFT, TA_CENTER, TA_JUSTIFY
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Preformatted, Table, TableStyle,
    ListFlowable, ListItem, KeepTogether, HRFlowable, PageBreak,
)

OUT = r"E:\文件\VANACANDLES\jQuery-distributor-click詳解.pdf"

pdfmetrics.registerFont(TTFont("JH", r"C:\Windows\Fonts\msjh.ttc", subfontIndex=0))
pdfmetrics.registerFont(TTFont("JHB", r"C:\Windows\Fonts\msjhbd.ttc", subfontIndex=0))
pdfmetrics.registerFont(TTFont("Mono", r"C:\Windows\Fonts\consola.ttf"))

CREAM = HexColor("#FFF7F1")
ROSE = HexColor("#8f7174")
INK = HexColor("#1f1612")
WARM = HexColor("#EFE4DC")
CODE_BG = HexColor("#2b2422")
CODE_FG = HexColor("#F6EDE6")
MUTED = HexColor("#5c504c")

PAGE_W, PAGE_H = A4
MARGIN = 18 * mm


def header_footer(canvas, doc):
    canvas.saveState()
    canvas.setFillColor(ROSE)
    canvas.rect(0, PAGE_H - 14 * mm, PAGE_W, 14 * mm, fill=1, stroke=0)
    canvas.setFillColor(white)
    canvas.setFont("JH", 9)
    canvas.drawString(MARGIN, PAGE_H - 9 * mm, "TopPun香．芬 ｜ jQuery 事件綁定詳解")
    canvas.drawRightString(PAGE_W - MARGIN, PAGE_H - 9 * mm, "index.php")
    canvas.setFillColor(ROSE)
    canvas.rect(0, 0, PAGE_W, 12 * mm, fill=1, stroke=0)
    canvas.setFillColor(white)
    canvas.setFont("JH", 8)
    canvas.drawString(MARGIN, 4.5 * mm, "© 2026 TopPun香．芬")
    canvas.drawRightString(PAGE_W - MARGIN, 4.5 * mm, f"{doc.page}")
    canvas.restoreState()


styles = getSampleStyleSheet()
styles.add(ParagraphStyle(
    "CoverTitle", fontName="JHB", fontSize=22, leading=30, textColor=INK,
    alignment=TA_CENTER, spaceAfter=8,
))
styles.add(ParagraphStyle(
    "CoverSub", fontName="JH", fontSize=12, leading=18, textColor=ROSE,
    alignment=TA_CENTER, spaceAfter=16,
))
styles.add(ParagraphStyle(
    "H1", fontName="JHB", fontSize=15, leading=22, textColor=INK,
    spaceBefore=14, spaceAfter=8,
))
styles.add(ParagraphStyle(
    "H2", fontName="JHB", fontSize=12.5, leading=18, textColor=ROSE,
    spaceBefore=10, spaceAfter=6,
))
styles.add(ParagraphStyle(
    "Body", fontName="JH", fontSize=10.5, leading=17, textColor=INK,
    alignment=TA_LEFT, spaceAfter=7,
))
styles.add(ParagraphStyle(
    "BulletBody", fontName="JH", fontSize=10.5, leading=17, textColor=INK,
    leftIndent=4, spaceAfter=3,
))
styles.add(ParagraphStyle(
    "Note", fontName="JH", fontSize=10, leading=16, textColor=MUTED,
    backColor=WARM, borderPadding=8, spaceAfter=10, spaceBefore=4,
))
styles.add(ParagraphStyle(
    "Caption", fontName="JH", fontSize=9, leading=13, textColor=MUTED,
    alignment=TA_CENTER, spaceAfter=10, spaceBefore=2,
))
styles.add(ParagraphStyle(
    "CodeInline", fontName="Mono", fontSize=9, leading=13, textColor=INK,
))
styles.add(ParagraphStyle(
    "CodeBlock", fontName="Mono", fontSize=8.4, leading=12.4, textColor=CODE_FG,
    backColor=CODE_BG, leftIndent=8, rightIndent=8, spaceBefore=4, spaceAfter=8,
))
styles.add(ParagraphStyle(
    "Th", fontName="JHB", fontSize=9.5, leading=14, textColor=white,
))
styles.add(ParagraphStyle(
    "Td", fontName="JH", fontSize=9.5, leading=14, textColor=INK,
))
styles.add(ParagraphStyle(
    "TdMono", fontName="Mono", fontSize=8.2, leading=12, textColor=INK,
))


def h1(text):
    return KeepTogether([
        Paragraph(text, styles["H1"]),
        HRFlowable(width="100%", thickness=0.6, color=ROSE, spaceAfter=6),
    ])


def code_block(text):
    inner = Preformatted(
        text.rstrip() + "\n",
        ParagraphStyle(
            "CodeInner",
            fontName="JH",
            fontSize=8.4,
            leading=13,
            textColor=CODE_FG,
        ),
    )
    t = Table([[inner]], colWidths=[PAGE_W - 2 * MARGIN])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), CODE_BG),
        ("LEFTPADDING", (0, 0), (-1, -1), 10),
        ("RIGHTPADDING", (0, 0), (-1, -1), 10),
        ("TOPPADDING", (0, 0), (-1, -1), 8),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 8),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
    ]))
    t.spaceAfter = 8
    t.spaceBefore = 3
    return t


def note_box(text):
    inner = Paragraph(text, ParagraphStyle(
        "NoteInner", fontName="JH", fontSize=10, leading=16, textColor=MUTED,
    ))
    t = Table([[inner]], colWidths=[PAGE_W - 2 * MARGIN])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), WARM),
        ("LEFTPADDING", (0, 0), (-1, -1), 10),
        ("RIGHTPADDING", (0, 0), (-1, -1), 10),
        ("TOPPADDING", (0, 0), (-1, -1), 8),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 8),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
    ]))
    t.spaceAfter = 10
    t.spaceBefore = 4
    return t


def pill_table(rows):
    data = [[Paragraph(c, styles["Th"] if i == 0 else styles["Td"])
             for j, c in enumerate(row)]
            for i, row in enumerate(rows)]
    col_w = [48 * mm, 122 * mm]
    t = Table(data, colWidths=col_w, repeatRows=1)
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), ROSE),
        ("BACKGROUND", (0, 1), (-1, -1), CREAM),
        ("TEXTCOLOR", (0, 0), (-1, 0), white),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 7),
        ("RIGHTPADDING", (0, 0), (-1, -1), 7),
        ("TOPPADDING", (0, 0), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
        ("GRID", (0, 0), (-1, -1), 0.3, HexColor("#d3c4b1")),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [CREAM, white]),
    ]))
    return t


story = []

story.append(Spacer(1, 8 * mm))
story.append(Paragraph("jQuery 點擊事件詳解", styles["CoverTitle"]))
story.append(Paragraph("$('.distributor-item').on('click', function () { … })", styles["CoverSub"]))
story.append(Paragraph(
    "這份文件說明首頁「合作經銷通路」如何用 jQuery 綁定點擊，"
    "點某一間店家後，右邊 OpenStreetMap 會切到對應位置。"
    "程式寫在 <font face='Mono'>index.php</font> 頁尾。",
    styles["Body"],
))

story.append(h1("一、完整程式（實際使用的版本）"))
story.append(Paragraph("HTML：每一間店家是一個區塊，座標存在 data 屬性裡。", styles["Body"]))
story.append(code_block("""<div class="distributor-item" data-lat="23.0438" data-lon="120.2461">
    <strong>GINANA 旗艦店</strong>
    <small>710 台南市永康區正南三路75號</small>
</div>"""))
story.append(Paragraph("JavaScript：先載入 jQuery，再綁定 click，最後呼叫 updateMap()。", styles["Body"]))
story.append(code_block("""$(function () {
    $('.distributor-item').on('click', function () {
        const lat = parseFloat($(this).data('lat'));
        const lon = parseFloat($(this).data('lon'));
        updateMap(lat, lon, this);
    });
});"""))

story.append(h1("二、由外到內拆解"))
story.append(Paragraph(
    "這段可以想成四層：等到網頁準備好 → 找出所有店家 → 監聽滑鼠點擊 → 讀座標並換地圖。",
    styles["Body"],
))

story.append(Paragraph("1. $(function () { … })　文件就緒", styles["H2"]))
story.append(Paragraph(
    "這是 <font face='Mono'>$(document).ready(function () { … })</font> 的短寫。"
    "瀏覽器會先畫 HTML，腳本若太早執行，<font face='Mono'>.distributor-item</font> 可能還不存在，"
    "綁定就會失敗。包在 <font face='Mono'>$(function () { })</font> 裡，保證 DOM 建好才跑。"
    "本頁腳本放在 <font face='Mono'>&lt;/body&gt;</font> 前，理論上已經看得到元素；"
    "加上 ready 是保險，也是 jQuery 的標準寫法。",
    styles["Body"],
))

story.append(Paragraph("2. $('.distributor-item')　選擇器", styles["H2"]))
story.append(Paragraph(
    "<font face='Mono'>$()</font> 是 jQuery 的入場券。字串參數用的是 CSS 選擇器。"
    "<font face='Mono'>'.distributor-item'</font> 代表「所有 class 含 distributor-item 的元素」。"
    "頁面上五間店家都會被選到，一次綁定，不必寫五次。",
    styles["Body"],
))
story.append(pill_table([
    ["寫法", "意思"],
    ["$('.distributor-item')", "所有 class 為 distributor-item 的元素（本例）"],
    ["$('#osm-map')", "id 為 osm-map 的那一個元素"],
    ["$('div')", "所有 div"],
    ["$('.distributor-item.active')", "同時有兩個 class 的元素"],
]))
story.append(Spacer(1, 3 * mm))

story.append(Paragraph("3. .on('click', handler)　委派／綁定事件", styles["H2"]))
story.append(Paragraph(
    "<font face='Mono'>.on()</font> 是 jQuery 統一的事件 API。"
    "第一個參數是事件名稱 <font face='Mono'>'click'</font>，"
    "第二個參數是「事件發生時要執行的函式」。"
    "使用者點到任一 <font face='Mono'>.distributor-item</font>，這個函式就會被呼叫一次。",
    styles["Body"],
))
story.append(Paragraph(
    "為什麼不用 HTML 的 <font face='Mono'>onclick=\"updateMap(...)\"</font>？"
    "舊寫法把行為寫死在標籤裡，座標改一次要動 HTML 與 JS 兩處，也比較難維護。"
    "現在座標只寫在 <font face='Mono'>data-lat</font> / <font face='Mono'>data-lon</font>，"
    "行為集中在一處 <font face='Mono'>.on('click')</font>。",
    styles["Body"],
))
story.append(pill_table([
    ["方法", "說明"],
    [".on('click', fn)", "建議。可綁多個處理函式，也可之後 .off() 解除"],
    [".click(fn)", "舊捷徑，內部還是呼叫 .on('click')"],
    ["onclick=\"...\"", "寫在 HTML 上，結構與行為混在一起"],
    ["addEventListener", "原生 JS，不需 jQuery，概念相同"],
]))

story.append(Paragraph("4. function () { … }　回呼函式與 this", styles["H2"]))
story.append(Paragraph(
    "這是匿名函式，當作 click 的 callback。每次點擊都會新建一次執行環境。"
    "在這個函式裡，<font face='Mono'>this</font> 指向「被點到的那一個 DOM 元素」"
    "（原生 HTMLElement，不是 jQuery 物件）。"
    "所以點旗艦店時 <font face='Mono'>this</font> 是旗艦店那個 div；"
    "點誠品時就是誠品那個 div。",
    styles["Body"],
))
story.append(note_box(
    "若改成箭頭函式 <font face='Mono'>() =&gt; { }</font>，"
    "<font face='Mono'>this</font> 不會指向被點元素（箭頭函式不綁定自己的 this），"
    "這段程式會壞掉。這裡必須用一般 <font face='Mono'>function ()</font>。"
))

story.append(h1("三、函式本體：三行在做什麼"))
story.append(code_block("""const lat = parseFloat($(this).data('lat'));
const lon = parseFloat($(this).data('lon'));
updateMap(lat, lon, this);"""))

story.append(Paragraph("$(this)", styles["H2"]))
story.append(Paragraph(
    "<font face='Mono'>this</font> 是原生 DOM。<font face='Mono'>$(this)</font> 把它包成 jQuery 物件，"
    "才能使用 <font face='Mono'>.data()</font>、<font face='Mono'>.addClass()</font> 等方法。"
    "可以想成：把原生元素包成 jQuery 物件。",
    styles["Body"],
))

story.append(Paragraph(".data('lat') 與 .data('lon')", styles["H2"]))
story.append(Paragraph(
    "HTML5 允許 <font face='Mono'>data-*</font> 自訂資料。"
    "jQuery 的 <font face='Mono'>.data('lat')</font> 會去讀 <font face='Mono'>data-lat</font>。"
    "注意：HTML 寫 <font face='Mono'>data-lat</font>，JS 只傳 <font face='Mono'>'lat'</font>，不要再寫 data-。",
    styles["Body"],
))
story.append(pill_table([
    ["HTML 屬性", "jQuery 讀法"],
    ["data-lat=\"23.0438\"", "$(this).data('lat')  →  23.0438"],
    ["data-lon=\"120.2461\"", "$(this).data('lon')  →  120.2461"],
    ["data-store-id=\"7\"", "$(this).data('storeId')  （連字號會轉駝峰）"],
]))
story.append(Spacer(1, 3 * mm))
story.append(Paragraph(
    "jQuery 會試著把看起來像數字的字串轉成 Number。"
    "為求明確，下面仍用 <font face='Mono'>parseFloat()</font> 再轉一次，保證傳進地圖的是浮點數，不是字串。",
    styles["Body"],
))

story.append(Paragraph("parseFloat(...)", styles["H2"]))
story.append(Paragraph(
    "<font face='Mono'>parseFloat('23.0438')</font> 得到數字 23.0438。"
    "若讀不到值會得到 <font face='Mono'>NaN</font>（Not a Number），地圖就會錯。"
    "因此 HTML 的 data-lat / data-lon 不能漏寫。",
    styles["Body"],
))

story.append(Paragraph("updateMap(lat, lon, this)", styles["H2"]))
story.append(Paragraph(
    "把緯度、經度，以及「被點到的那個元素」交給稍早定義的函式。"
    "第三個參數用原生 <font face='Mono'>this</font>，因為 updateMap 裡會對它做 "
    "<font face='Mono'>element.classList.add('active')</font>，那是 DOM API，不是 jQuery。",
    styles["Body"],
))
story.append(code_block("""function updateMap(lat, lon, element) {
    // 1. 先拿掉所有店家的 active，再只加在被點的那一間
    document.querySelectorAll('.distributor-item').forEach(item => {
        item.classList.remove('active');
    });
    if (element) {
        element.classList.add('active');
    }

    // 2. 用經緯度組 OpenStreetMap 嵌入網址與「查看更大的地圖」連結
    const bboxSize = 0.01;
    const embedUrl = `https://www.openstreetmap.org/export/embed.html?bbox=...`;
    const largeMapUrl = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}#map=16/...`;

    // 3. 換成新地圖
    document.getElementById('osm-map').src = embedUrl;
    document.getElementById('osm-large-link').href = largeMapUrl;
}"""))

story.append(h1("四、點擊之後實際發生的事（時間順序）"))
steps = [
    "使用者點「誠品生活南西」。",
    "瀏覽器發出 click 事件，jQuery 找到綁在 .distributor-item 上的處理函式。",
    "this 變成誠品那個 div。",
    "$(this).data('lat') 讀到 25.0523，data('lon') 讀到 121.5204。",
    "parseFloat 轉成數字。",
    "updateMap(25.0523, 121.5204, 誠品div) 被呼叫。",
    "五間店家都移除 active，只有誠品加上 active（視覺上標成目前選取）。",
    "iframe#osm-map 的 src 換成台北中山附近的 OSM 地圖。",
    "「查看更大的地圖」連結也改成同一座標。",
]
story.append(ListFlowable(
    [ListItem(Paragraph(s, styles["BulletBody"]), leftIndent=12, bulletColor=ROSE)
     for s in steps],
    bulletType="1",
    start="1",
    leftIndent=18,
    spaceAfter=8,
))

story.append(h1("五、和原生 JavaScript 的對照"))
story.append(Paragraph("同一件事不用 jQuery 可以這樣寫：", styles["Body"]))
story.append(code_block("""document.querySelectorAll('.distributor-item').forEach(function (el) {
    el.addEventListener('click', function () {
        const lat = parseFloat(this.dataset.lat);
        const lon = parseFloat(this.dataset.lon);
        updateMap(lat, lon, this);
    });
});"""))
story.append(pill_table([
    ["jQuery", "原生 JS"],
    ["$('.distributor-item')", "document.querySelectorAll('.distributor-item')"],
    [".on('click', fn)", "addEventListener('click', fn)"],
    ["$(this).data('lat')", "this.dataset.lat"],
    ["$(function () { … })", "document.addEventListener('DOMContentLoaded', …)"],
]))
story.append(Spacer(1, 3 * mm))
story.append(note_box(
    "本專案用 jQuery，是因為作業要求出現 <font face='Mono'>$('…').on(\"click\", …)</font> 這種寫法；"
    "功能上等價於上面的原生程式。"
))

story.append(h1("六、為什麼選擇器要加點（.）"))
story.append(Paragraph(
    "CSS 裡 <font face='Mono'>.class</font> 代表 class，<font face='Mono'>#id</font> 代表 id，"
    "沒有符號則是標籤名。漏掉點會變成「尋找名叫 distributor-item 的 HTML 標籤」，"
    "頁面上沒有這種標籤，結果是空集合，點了也不會有反應。",
    styles["Body"],
))
story.append(code_block("""$('.distributor-item')   // 正確：class
$('distributor-item')    // 錯誤：當成 <distributor-item> 標籤
$('#distributor-item')   // 錯誤：當成 id（本頁 id 不是這個）"""))

story.append(h1("七、常見錯誤"))
story.append(pill_table([
    ["狀況", "原因與解法"],
    ["點了沒反應", "jQuery 沒載入，或腳本在元素出現前就執行。檢查 script src 與 $(function(){})。"],
    ["this 是 window", "誤用箭頭函式 () =&gt; {}。改回 function () {}。"],
    ["lat / lon 是 undefined", "HTML 漏了 data-lat 或拼成 data-latitude。"],
    ["地圖跑到海裡", "經緯度寫反（lat 是緯度、lon 是經度）。"],
    ["只有第一間有效", "用了 $('#…') 或 querySelector（只取一個），應使用 class 選全部。"],
]))

story.append(Spacer(1, 6 * mm))
story.append(HRFlowable(width="100%", thickness=0.6, color=ROSE, spaceAfter=8))
story.append(Paragraph(
    "一句話記住：<b>$('.distributor-item')</b> 找到所有店家，"
    "<b>.on('click', function () { … })</b> 在點擊時用 <b>$(this).data()</b> 拿出該店座標，"
    "再交給 <b>updateMap()</b> 換地圖、標記目前選取的店。",
    styles["Body"],
))

doc = SimpleDocTemplate(
    OUT,
    pagesize=A4,
    leftMargin=MARGIN,
    rightMargin=MARGIN,
    topMargin=20 * mm,
    bottomMargin=16 * mm,
    title="jQuery $('.distributor-item').on('click') 詳解",
    author="TopPun香．芬",
)
doc.build(story, onFirstPage=header_footer, onLaterPages=header_footer)
print("WROTE", OUT)
