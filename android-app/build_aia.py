"""
Generates SMSDashboard.aia — clean rebuild.

Changes vs v2:
 - No assets/.placeholder (caused import issues)
 - Explicit directory entries in ZIP
 - Simplified SCM (fewer UI components, less chance of parse error)
 - project.properties: cleaned up fields
 - ZIP uses DEFLATED for files, directories are stored
"""
import zipfile, os, io

WEBHOOK = "https://sms.bharatseo.site/api/receive.php"

# ── project.properties ────────────────────────────────────────────────────
PROPS = (
    "main=appinventor.ai_user.SMSDashboard.Screen1\n"
    "name=SMSDashboard\n"
    "assets=../assets\n"
    "source=../src\n"
    "build=../build\n"
    "versioncode=3\n"
    "versionname=1.2\n"
    "useslocation=False\n"
    "aname=SMS Dashboard\n"
)

# ── Screen1.scm ──────────────────────────────────────────────────────────
SCM = (
    "#|\n"
    "$JSON\n"
    '{"YaVersion":"221","Source":"Form","Properties":{"$Name":"Screen1","$Type":"Form","$Version":"29",'
    '"AppName":"SMS Dashboard","BackgroundColor":"&HFF0F172A","PrimaryColor":"&HFF3B82F6",'
    '"Theme":"AppTheme.Dark.DarkActionBar","Sizing":"Responsive","Title":"SMS Dashboard",'
    '"$Components":['
      '{"$Name":"Texting1","$Type":"Texting","$Version":"2","ReceivingEnabled":"2"},'
      '{"$Name":"Web1","$Type":"Web","$Version":"6","Url":"' + WEBHOOK + '"},'
      '{"$Name":"TinyDB1","$Type":"TinyDB","$Version":"2","Namespace":"SMSDashboard"},'
      '{"$Name":"Notifier1","$Type":"Notifier","$Version":"3"},'
      '{"$Name":"LblTitle","$Type":"Label","$Version":"5","FontBold":"True","FontSize":"20",'
        '"Text":"SMS Dashboard","TextColor":"&HFFFFFFFF","Width":"-2"},'
      '{"$Name":"LblStatus","$Type":"Label","$Version":"5","FontSize":"12",'
        '"Text":"Listening for SMS...","TextColor":"&HFF4ADE80","Width":"-2"},'
      '{"$Name":"LblApiLabel","$Type":"Label","$Version":"5","FontSize":"13",'
        '"Text":"API Key:","TextColor":"&HFFFFFFFF"},'
      '{"$Name":"TxtApiKey","$Type":"TextBox","$Version":"6",'
        '"Hint":"Dashboard se copy karke yahan paste karo","Width":"-2"},'
      '{"$Name":"BtnSave","$Type":"Button","$Version":"7",'
        '"BackgroundColor":"&HFF3B82F6","FontBold":"True","Text":"Save API Key",'
        '"TextColor":"&HFFFFFFFF","Width":"-2"},'
      '{"$Name":"LblCount","$Type":"Label","$Version":"5","FontSize":"12",'
        '"Text":"Forwarded: 0","TextColor":"&HFF94A3B8","Width":"-2"},'
      '{"$Name":"LblLast","$Type":"Label","$Version":"5","FontSize":"11",'
        '"Text":"Last: (none)","TextColor":"&HFF94A3B8","Width":"-2"}'
    ']}}\n'
    "$JSON\n"
    "|#\n"
)

# ── Screen1.bky ───────────────────────────────────────────────────────────
BKY = """\
<xml xmlns="http://www.w3.org/1999/xhtml">

  <!-- Screen1.Initialize: restore saved API key and counter -->
  <block type="component_event" id="e1" x="20" y="20">
    <mutation component_type="Form" event_name="Initialize" is_generic="false" instance_name="Screen1"></mutation>
    <field name="COMPONENT_SELECTOR">Screen1</field>
    <statement name="DO">
      <block type="component_set_get" id="e2">
        <mutation component_type="TextBox" set_or_get="set" property_name="Text" is_generic="false" instance_name="TxtApiKey"></mutation>
        <field name="COMPONENT_SELECTOR">TxtApiKey</field>
        <field name="PROP">Text</field>
        <value name="VALUE">
          <block type="component_method" id="e3">
            <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
            <field name="COMPONENT_SELECTOR">TinyDB1</field>
            <value name="ARG0"><block type="text" id="e4"><field name="TEXT">api_key</field></block></value>
            <value name="ARG1"><block type="text" id="e5"><field name="TEXT"></field></block></value>
          </block>
        </value>
        <next>
          <block type="component_set_get" id="e6">
            <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblCount"></mutation>
            <field name="COMPONENT_SELECTOR">LblCount</field>
            <field name="PROP">Text</field>
            <value name="VALUE">
              <block type="text_join" id="e7">
                <mutation items="2"></mutation>
                <value name="ADD0"><block type="text" id="e8"><field name="TEXT">Forwarded: </field></block></value>
                <value name="ADD1">
                  <block type="component_method" id="e9">
                    <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                    <field name="COMPONENT_SELECTOR">TinyDB1</field>
                    <value name="ARG0"><block type="text" id="e10"><field name="TEXT">fwd_count</field></block></value>
                    <value name="ARG1"><block type="math_number" id="e11"><field name="NUM">0</field></block></value>
                  </block>
                </value>
              </block>
            </value>
          </block>
        </next>
      </block>
    </statement>
  </block>

  <!-- BtnSave.Click: persist API key -->
  <block type="component_event" id="s1" x="20" y="260">
    <mutation component_type="Button" event_name="Click" is_generic="false" instance_name="BtnSave"></mutation>
    <field name="COMPONENT_SELECTOR">BtnSave</field>
    <statement name="DO">
      <block type="component_method" id="s2">
        <mutation component_type="TinyDB" method_name="StoreValue" is_generic="false" instance_name="TinyDB1"></mutation>
        <field name="COMPONENT_SELECTOR">TinyDB1</field>
        <value name="ARG0"><block type="text" id="s3"><field name="TEXT">api_key</field></block></value>
        <value name="ARG1">
          <block type="component_set_get" id="s4">
            <mutation component_type="TextBox" set_or_get="get" property_name="Text" is_generic="false" instance_name="TxtApiKey"></mutation>
            <field name="COMPONENT_SELECTOR">TxtApiKey</field>
            <field name="PROP">Text</field>
          </block>
        </value>
        <next>
          <block type="component_method" id="s5">
            <mutation component_type="Notifier" method_name="ShowToast" is_generic="false" instance_name="Notifier1"></mutation>
            <field name="COMPONENT_SELECTOR">Notifier1</field>
            <value name="ARG0"><block type="text" id="s6"><field name="TEXT">API Key save ho gaya!</field></block></value>
          </block>
        </next>
      </block>
    </statement>
  </block>

  <!-- Texting1.MessageReceived: forward to server -->
  <block type="component_event" id="r1" x="440" y="20">
    <mutation component_type="Texting" event_name="MessageReceived" is_generic="false" instance_name="Texting1"></mutation>
    <field name="COMPONENT_SELECTOR">Texting1</field>
    <statement name="DO">
      <block type="controls_if" id="r2">
        <mutation else="1"></mutation>
        <value name="IF0">
          <block type="text_isEmpty" id="r3">
            <value name="VALUE">
              <block type="component_method" id="r4">
                <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                <field name="COMPONENT_SELECTOR">TinyDB1</field>
                <value name="ARG0"><block type="text" id="r5"><field name="TEXT">api_key</field></block></value>
                <value name="ARG1"><block type="text" id="r6"><field name="TEXT"></field></block></value>
              </block>
            </value>
          </block>
        </value>
        <statement name="DO0">
          <block type="component_method" id="r7">
            <mutation component_type="Notifier" method_name="ShowToast" is_generic="false" instance_name="Notifier1"></mutation>
            <field name="COMPONENT_SELECTOR">Notifier1</field>
            <value name="ARG0"><block type="text" id="r8"><field name="TEXT">API Key pehle save karo!</field></block></value>
          </block>
        </statement>
        <statement name="ELSE">
          <block type="component_set_get" id="r9">
            <mutation component_type="Web" set_or_get="set" property_name="RequestHeaders" is_generic="false" instance_name="Web1"></mutation>
            <field name="COMPONENT_SELECTOR">Web1</field>
            <field name="PROP">RequestHeaders</field>
            <value name="VALUE">
              <block type="lists_create_with" id="r10">
                <mutation items="1"></mutation>
                <value name="ADD0">
                  <block type="lists_create_with" id="r11">
                    <mutation items="2"></mutation>
                    <value name="ADD0"><block type="text" id="r12"><field name="TEXT">Content-Type</field></block></value>
                    <value name="ADD1"><block type="text" id="r13"><field name="TEXT">application/x-www-form-urlencoded</field></block></value>
                  </block>
                </value>
              </block>
            </value>
            <next>
              <block type="component_method" id="r14">
                <mutation component_type="Web" method_name="PostText" is_generic="false" instance_name="Web1"></mutation>
                <field name="COMPONENT_SELECTOR">Web1</field>
                <value name="ARG0">
                  <block type="text_join" id="r15">
                    <mutation items="6"></mutation>
                    <value name="ADD0"><block type="text" id="r16"><field name="TEXT">api_key=</field></block></value>
                    <value name="ADD1">
                      <block type="component_method" id="r17">
                        <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                        <field name="COMPONENT_SELECTOR">TinyDB1</field>
                        <value name="ARG0"><block type="text" id="r18"><field name="TEXT">api_key</field></block></value>
                        <value name="ARG1"><block type="text" id="r19"><field name="TEXT"></field></block></value>
                      </block>
                    </value>
                    <value name="ADD2"><block type="text" id="r20"><field name="TEXT">&amp;sender=</field></block></value>
                    <value name="ADD3">
                      <block type="component_method" id="r21">
                        <mutation component_type="Web" method_name="UriEncode" is_generic="false" instance_name="Web1"></mutation>
                        <field name="COMPONENT_SELECTOR">Web1</field>
                        <value name="ARG0"><block type="lexical_variable_get" id="r22"><field name="VAR">number</field></block></value>
                      </block>
                    </value>
                    <value name="ADD4"><block type="text" id="r23"><field name="TEXT">&amp;message=</field></block></value>
                    <value name="ADD5">
                      <block type="component_method" id="r24">
                        <mutation component_type="Web" method_name="UriEncode" is_generic="false" instance_name="Web1"></mutation>
                        <field name="COMPONENT_SELECTOR">Web1</field>
                        <value name="ARG0"><block type="lexical_variable_get" id="r25"><field name="VAR">messageText</field></block></value>
                      </block>
                    </value>
                  </block>
                </value>
                <next>
                  <block type="component_set_get" id="r26">
                    <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblLast"></mutation>
                    <field name="COMPONENT_SELECTOR">LblLast</field>
                    <field name="PROP">Text</field>
                    <value name="VALUE">
                      <block type="text_join" id="r27">
                        <mutation items="2"></mutation>
                        <value name="ADD0"><block type="text" id="r28"><field name="TEXT">Sending: </field></block></value>
                        <value name="ADD1"><block type="lexical_variable_get" id="r29"><field name="VAR">number</field></block></value>
                      </block>
                    </value>
                  </block>
                </next>
              </block>
            </next>
          </block>
        </statement>
      </block>
    </statement>
  </block>

  <!-- Web1.GotText: show result -->
  <block type="component_event" id="g1" x="440" y="500">
    <mutation component_type="Web" event_name="GotText" is_generic="false" instance_name="Web1"></mutation>
    <field name="COMPONENT_SELECTOR">Web1</field>
    <statement name="DO">
      <block type="controls_if" id="g2">
        <mutation else="1"></mutation>
        <value name="IF0">
          <block type="logic_compare" id="g3">
            <field name="OP">EQ</field>
            <value name="A"><block type="lexical_variable_get" id="g4"><field name="VAR">responseCode</field></block></value>
            <value name="B"><block type="math_number" id="g5"><field name="NUM">200</field></block></value>
          </block>
        </value>
        <statement name="DO0">
          <block type="component_method" id="g6">
            <mutation component_type="TinyDB" method_name="StoreValue" is_generic="false" instance_name="TinyDB1"></mutation>
            <field name="COMPONENT_SELECTOR">TinyDB1</field>
            <value name="ARG0"><block type="text" id="g7"><field name="TEXT">fwd_count</field></block></value>
            <value name="ARG1">
              <block type="math_arithmetic" id="g8">
                <field name="OP">ADD</field>
                <value name="A">
                  <block type="component_method" id="g9">
                    <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                    <field name="COMPONENT_SELECTOR">TinyDB1</field>
                    <value name="ARG0"><block type="text" id="g10"><field name="TEXT">fwd_count</field></block></value>
                    <value name="ARG1"><block type="math_number" id="g11"><field name="NUM">0</field></block></value>
                  </block>
                </value>
                <value name="B"><block type="math_number" id="g12"><field name="NUM">1</field></block></value>
              </block>
            </value>
            <next>
              <block type="component_set_get" id="g13">
                <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblCount"></mutation>
                <field name="COMPONENT_SELECTOR">LblCount</field>
                <field name="PROP">Text</field>
                <value name="VALUE">
                  <block type="text_join" id="g14">
                    <mutation items="2"></mutation>
                    <value name="ADD0"><block type="text" id="g15"><field name="TEXT">Forwarded: </field></block></value>
                    <value name="ADD1">
                      <block type="component_method" id="g16">
                        <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                        <field name="COMPONENT_SELECTOR">TinyDB1</field>
                        <value name="ARG0"><block type="text" id="g17"><field name="TEXT">fwd_count</field></block></value>
                        <value name="ARG1"><block type="math_number" id="g18"><field name="NUM">0</field></block></value>
                      </block>
                    </value>
                  </block>
                </value>
                <next>
                  <block type="component_set_get" id="g19">
                    <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblLast"></mutation>
                    <field name="COMPONENT_SELECTOR">LblLast</field>
                    <field name="PROP">Text</field>
                    <value name="VALUE"><block type="text" id="g20"><field name="TEXT">Last: Forwarded!</field></block></value>
                  </block>
                </next>
              </block>
            </next>
          </block>
        </statement>
        <statement name="ELSE">
          <block type="component_set_get" id="g21">
            <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblLast"></mutation>
            <field name="COMPONENT_SELECTOR">LblLast</field>
            <field name="PROP">Text</field>
            <value name="VALUE">
              <block type="text_join" id="g22">
                <mutation items="2"></mutation>
                <value name="ADD0"><block type="text" id="g23"><field name="TEXT">Error: code </field></block></value>
                <value name="ADD1"><block type="lexical_variable_get" id="g24"><field name="VAR">responseCode</field></block></value>
              </block>
            </value>
          </block>
        </statement>
      </block>
    </statement>
  </block>

</xml>
"""

# ── Build .aia ─────────────────────────────────────────────────────────────
SRC = "src/appinventor/ai_user/SMSDashboard"
out  = "SMSDashboard.aia"

with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED, allowZip64=False) as z:
    # Explicit directory entries (App Inventor expects these)
    for d in ["youngandroidproject/", "src/", "src/appinventor/",
              "src/appinventor/ai_user/", f"src/appinventor/ai_user/SMSDashboard/",
              "assets/"]:
        info = zipfile.ZipInfo(d)
        info.compress_type = zipfile.ZIP_STORED
        z.writestr(info, "")

    z.writestr("youngandroidproject/project.properties", PROPS)
    z.writestr(f"{SRC}/Screen1.scm", SCM)
    z.writestr(f"{SRC}/Screen1.bky", BKY)

print(f"Created: {out}  ({os.path.getsize(out):,} bytes)")

with zipfile.ZipFile(out) as z:
    bad = z.testzip()
    print("ZIP: CORRUPT=" + str(bad) if bad else "ZIP: OK")
    for i in z.infolist():
        print(f"  {'DIR' if i.filename.endswith('/') else 'FILE':4s}  {i.filename}")

import xml.etree.ElementTree as ET
with zipfile.ZipFile(out) as z:
    ET.fromstring(z.read(f"{SRC}/Screen1.bky"))
    print("BKY XML: valid")
