"""
Generates SMSDashboard.aia for MIT App Inventor — fixed version.

Fixes vs v1:
 - math_add → math_arithmetic (OP=ADD)
 - controls_if with else now has <mutation else="1">
 - JSON body replaced with application/x-www-form-urlencoded + Web.UriEncode
   (handles any special characters in SMS messages)
 - local_declaration removed — simpler inline TinyDB calls
 - Web1.GotText shows success/fail status in UI

Domain: sms.bharatseo.site
"""
import zipfile, os

PROJECT_PROPS = """\
main=appinventor.ai_user.SMSDashboard.Screen1
name=SMSDashboard
assets=../assets
source=../src
build=../build
versioncode=2
versionname=1.1
useslocation=False
aname=SMS Dashboard
"""

# ── Screen SCM (UI components) ─────────────────────────────────────────────
SCREEN_SCM = r"""#|
$JSON
{"YaVersion":"221","Source":"Form","Properties":{"$Name":"Screen1","$Type":"Form","$Version":"29","AppName":"SMS Dashboard","BackgroundColor":"&HFF0F172A","PrimaryColor":"&HFF3B82F6","PrimaryColorDark":"&HFF1E40AF","AccentColor":"&HFF60A5FA","Theme":"AppTheme.Dark.DarkActionBar","Sizing":"Responsive","ShowStatusBar":"True","Title":"SMS Dashboard","TitleVisible":"True","$Components":[{"$Name":"Texting1","$Type":"Texting","$Version":"2","ReceivingEnabled":"2"},{"$Name":"Web1","$Type":"Web","$Version":"6","Url":"https://sms.bharatseo.site/api/receive.php"},{"$Name":"TinyDB1","$Type":"TinyDB","$Version":"2","Namespace":"SMSDashboard"},{"$Name":"Notifier1","$Type":"Notifier","$Version":"3"},{"$Name":"ColHeader","$Type":"VerticalArrangement","$Version":"4","AlignHorizontal":"3","AlignVertical":"2","BackgroundColor":"&HFF1E3A5F","Height":"160","Width":"-2","$Components":[{"$Name":"LblIcon","$Type":"Label","$Version":"5","FontSize":"40","HasMargins":"False","Text":"📱","Width":"-2"},{"$Name":"LblTitle","$Type":"Label","$Version":"5","FontBold":"True","FontSize":"20","HasMargins":"False","Text":"SMS Dashboard","TextColor":"&HFFFFFFFF","Width":"-2"},{"$Name":"LblSub","$Type":"Label","$Version":"5","FontSize":"11","HasMargins":"False","Text":"sms.bharatseo.site","TextColor":"&HFF93C5FD","Width":"-2"}]},{"$Name":"ColBody","$Type":"VerticalArrangement","$Version":"4","BackgroundColor":"&HFF0F172A","Height":"-2","Width":"-2","$Components":[{"$Name":"ColApiCard","$Type":"VerticalArrangement","$Version":"4","BackgroundColor":"&HFF1E293B","Height":"-2","Width":"-2","$Components":[{"$Name":"LblApiTitle","$Type":"Label","$Version":"5","FontBold":"True","FontSize":"13","Text":"🔑 API Key","TextColor":"&HFFFFFFFF"},{"$Name":"LblApiHint","$Type":"Label","$Version":"5","FontSize":"11","Text":"Dashboard → API Settings → Copy karo","TextColor":"&HFF64748B"},{"$Name":"TxtApiKey","$Type":"TextBox","$Version":"6","BackgroundColor":"&HFF0F172A","FontSize":"12","Hint":"Apna API key yahan paste karo...","TextColor":"&HFFFFFFFF","Width":"-2"},{"$Name":"BtnSave","$Type":"Button","$Version":"7","BackgroundColor":"&HFF3B82F6","FontBold":"True","FontSize":"14","Text":"💾  Save API Key","TextColor":"&HFFFFFFFF","Width":"-2"}]},{"$Name":"ColStatusCard","$Type":"VerticalArrangement","$Version":"4","BackgroundColor":"&HFF1E293B","Height":"-2","Width":"-2","$Components":[{"$Name":"LblStatusTitle","$Type":"Label","$Version":"5","FontBold":"True","FontSize":"13","Text":"📊 Status","TextColor":"&HFFFFFFFF"},{"$Name":"LblLive","$Type":"Label","$Version":"5","FontSize":"13","Text":"🟢  Listening for SMS...","TextColor":"&HFF4ADE80","Width":"-2"},{"$Name":"LblCount","$Type":"Label","$Version":"5","FontSize":"12","Text":"Forwarded: 0 messages","TextColor":"&HFF94A3B8","Width":"-2"},{"$Name":"LblLastSms","$Type":"Label","$Version":"5","FontSize":"11","Text":"Last: Koi message nahi aaya abhi","TextColor":"&HFF94A3B8","Width":"-2"}]}]}]}}
$JSON
|#
"""

# ── Screen BKY (Blocks logic) ──────────────────────────────────────────────
SCREEN_BKY = """\
<xml xmlns="http://www.w3.org/1999/xhtml">

  <!-- ═══ Screen1.Initialize: load saved API key + count ═══ -->
  <block type="component_event" id="b1" x="20" y="20">
    <mutation component_type="Form" event_name="Initialize" is_generic="false" instance_name="Screen1"></mutation>
    <field name="COMPONENT_SELECTOR">Screen1</field>
    <statement name="DO">
      <block type="component_set_get" id="b2">
        <mutation component_type="TextBox" set_or_get="set" property_name="Text" is_generic="false" instance_name="TxtApiKey"></mutation>
        <field name="COMPONENT_SELECTOR">TxtApiKey</field>
        <field name="PROP">Text</field>
        <value name="VALUE">
          <block type="component_method" id="b3">
            <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
            <field name="COMPONENT_SELECTOR">TinyDB1</field>
            <value name="ARG0"><block type="text" id="b4"><field name="TEXT">api_key</field></block></value>
            <value name="ARG1"><block type="text" id="b5"><field name="TEXT"></field></block></value>
          </block>
        </value>
        <next>
          <block type="component_set_get" id="b6">
            <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblCount"></mutation>
            <field name="COMPONENT_SELECTOR">LblCount</field>
            <field name="PROP">Text</field>
            <value name="VALUE">
              <block type="text_join" id="b7">
                <mutation items="2"></mutation>
                <value name="ADD0"><block type="text" id="b8"><field name="TEXT">Forwarded: </field></block></value>
                <value name="ADD1">
                  <block type="component_method" id="b9">
                    <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                    <field name="COMPONENT_SELECTOR">TinyDB1</field>
                    <value name="ARG0"><block type="text" id="b10"><field name="TEXT">fwd_count</field></block></value>
                    <value name="ARG1"><block type="math_number" id="b11"><field name="NUM">0</field></block></value>
                  </block>
                </value>
              </block>
            </value>
          </block>
        </next>
      </block>
    </statement>
  </block>

  <!-- ═══ BtnSave.Click: save API key to TinyDB ═══ -->
  <block type="component_event" id="b20" x="20" y="280">
    <mutation component_type="Button" event_name="Click" is_generic="false" instance_name="BtnSave"></mutation>
    <field name="COMPONENT_SELECTOR">BtnSave</field>
    <statement name="DO">
      <block type="component_method" id="b21">
        <mutation component_type="TinyDB" method_name="StoreValue" is_generic="false" instance_name="TinyDB1"></mutation>
        <field name="COMPONENT_SELECTOR">TinyDB1</field>
        <value name="ARG0"><block type="text" id="b22"><field name="TEXT">api_key</field></block></value>
        <value name="ARG1">
          <block type="component_set_get" id="b23">
            <mutation component_type="TextBox" set_or_get="get" property_name="Text" is_generic="false" instance_name="TxtApiKey"></mutation>
            <field name="COMPONENT_SELECTOR">TxtApiKey</field>
            <field name="PROP">Text</field>
          </block>
        </value>
        <next>
          <block type="component_method" id="b24">
            <mutation component_type="Notifier" method_name="ShowToast" is_generic="false" instance_name="Notifier1"></mutation>
            <field name="COMPONENT_SELECTOR">Notifier1</field>
            <value name="ARG0"><block type="text" id="b25"><field name="TEXT">API Key save ho gaya! Ab SMS forward hoga.</field></block></value>
          </block>
        </next>
      </block>
    </statement>
  </block>

  <!-- ═══ Texting1.MessageReceived: forward SMS to server ═══ -->
  <block type="component_event" id="b30" x="440" y="20">
    <mutation component_type="Texting" event_name="MessageReceived" is_generic="false" instance_name="Texting1"></mutation>
    <field name="COMPONENT_SELECTOR">Texting1</field>
    <statement name="DO">

      <!-- Check if API key is set -->
      <block type="controls_if" id="b31">
        <mutation else="1"></mutation>
        <value name="IF0">
          <block type="text_isEmpty" id="b32">
            <value name="VALUE">
              <block type="component_method" id="b33">
                <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                <field name="COMPONENT_SELECTOR">TinyDB1</field>
                <value name="ARG0"><block type="text" id="b34"><field name="TEXT">api_key</field></block></value>
                <value name="ARG1"><block type="text" id="b35"><field name="TEXT"></field></block></value>
              </block>
            </value>
          </block>
        </value>

        <!-- IF empty: show warning -->
        <statement name="DO0">
          <block type="component_method" id="b36">
            <mutation component_type="Notifier" method_name="ShowToast" is_generic="false" instance_name="Notifier1"></mutation>
            <field name="COMPONENT_SELECTOR">Notifier1</field>
            <value name="ARG0"><block type="text" id="b37"><field name="TEXT">API Key nahi mila! Pehle save karo.</field></block></value>
          </block>
        </statement>

        <!-- ELSE: send to server -->
        <statement name="ELSE">

          <!-- Set Content-Type header -->
          <block type="component_set_get" id="b38">
            <mutation component_type="Web" set_or_get="set" property_name="RequestHeaders" is_generic="false" instance_name="Web1"></mutation>
            <field name="COMPONENT_SELECTOR">Web1</field>
            <field name="PROP">RequestHeaders</field>
            <value name="VALUE">
              <block type="lists_create_with" id="b39">
                <mutation items="1"></mutation>
                <value name="ADD0">
                  <block type="lists_create_with" id="b40">
                    <mutation items="2"></mutation>
                    <value name="ADD0"><block type="text" id="b41"><field name="TEXT">Content-Type</field></block></value>
                    <value name="ADD1"><block type="text" id="b42"><field name="TEXT">application/x-www-form-urlencoded</field></block></value>
                  </block>
                </value>
              </block>
            </value>

            <next>
              <!-- POST: api_key=...&sender=ENCODED&message=ENCODED&device=Android -->
              <block type="component_method" id="b43">
                <mutation component_type="Web" method_name="PostText" is_generic="false" instance_name="Web1"></mutation>
                <field name="COMPONENT_SELECTOR">Web1</field>
                <value name="ARG0">
                  <block type="text_join" id="b44">
                    <mutation items="8"></mutation>

                    <value name="ADD0"><block type="text" id="b45"><field name="TEXT">api_key=</field></block></value>

                    <!-- API key (no encode needed — no special chars) -->
                    <value name="ADD1">
                      <block type="component_method" id="b46">
                        <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                        <field name="COMPONENT_SELECTOR">TinyDB1</field>
                        <value name="ARG0"><block type="text" id="b47"><field name="TEXT">api_key</field></block></value>
                        <value name="ARG1"><block type="text" id="b48"><field name="TEXT"></field></block></value>
                      </block>
                    </value>

                    <value name="ADD2"><block type="text" id="b49"><field name="TEXT">&amp;sender=</field></block></value>

                    <!-- UriEncode sender (handles +91, special chars) -->
                    <value name="ADD3">
                      <block type="component_method" id="b50">
                        <mutation component_type="Web" method_name="UriEncode" is_generic="false" instance_name="Web1"></mutation>
                        <field name="COMPONENT_SELECTOR">Web1</field>
                        <value name="ARG0">
                          <block type="lexical_variable_get" id="b51"><field name="VAR">number</field></block>
                        </value>
                      </block>
                    </value>

                    <value name="ADD4"><block type="text" id="b52"><field name="TEXT">&amp;message=</field></block></value>

                    <!-- UriEncode message (handles quotes, newlines, &, = etc.) -->
                    <value name="ADD5">
                      <block type="component_method" id="b53">
                        <mutation component_type="Web" method_name="UriEncode" is_generic="false" instance_name="Web1"></mutation>
                        <field name="COMPONENT_SELECTOR">Web1</field>
                        <value name="ARG0">
                          <block type="lexical_variable_get" id="b54"><field name="VAR">messageText</field></block>
                        </value>
                      </block>
                    </value>

                    <value name="ADD6"><block type="text" id="b55"><field name="TEXT">&amp;device=Android+Phone</field></block></value>

                    <!-- Empty slot placeholder to keep items=8 -->
                    <value name="ADD7"><block type="text" id="b56"><field name="TEXT"></field></block></value>

                  </block>
                </value>

                <next>
                  <!-- Update status label: Sending... -->
                  <block type="component_set_get" id="b57">
                    <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblLastSms"></mutation>
                    <field name="COMPONENT_SELECTOR">LblLastSms</field>
                    <field name="PROP">Text</field>
                    <value name="VALUE">
                      <block type="text_join" id="b58">
                        <mutation items="3"></mutation>
                        <value name="ADD0"><block type="text" id="b59"><field name="TEXT">Sending: </field></block></value>
                        <value name="ADD1"><block type="lexical_variable_get" id="b60"><field name="VAR">number</field></block></value>
                        <value name="ADD2"><block type="text" id="b61"><field name="TEXT"> ⏳</field></block></value>
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

  <!-- ═══ Web1.GotText: handle server response ═══ -->
  <block type="component_event" id="b70" x="440" y="540">
    <mutation component_type="Web" event_name="GotText" is_generic="false" instance_name="Web1"></mutation>
    <field name="COMPONENT_SELECTOR">Web1</field>
    <statement name="DO">

      <block type="controls_if" id="b71">
        <mutation else="1"></mutation>

        <!-- IF responseCode == 200 -->
        <value name="IF0">
          <block type="logic_compare" id="b72">
            <field name="OP">EQ</field>
            <value name="A"><block type="lexical_variable_get" id="b73"><field name="VAR">responseCode</field></block></value>
            <value name="B"><block type="math_number" id="b74"><field name="NUM">200</field></block></value>
          </block>
        </value>

        <!-- DO: increment counter + update UI -->
        <statement name="DO0">
          <block type="component_method" id="b75">
            <mutation component_type="TinyDB" method_name="StoreValue" is_generic="false" instance_name="TinyDB1"></mutation>
            <field name="COMPONENT_SELECTOR">TinyDB1</field>
            <value name="ARG0"><block type="text" id="b76"><field name="TEXT">fwd_count</field></block></value>
            <value name="ARG1">

              <!-- fwd_count + 1  (using math_arithmetic — correct block name) -->
              <block type="math_arithmetic" id="b77">
                <field name="OP">ADD</field>
                <value name="A">
                  <block type="component_method" id="b78">
                    <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                    <field name="COMPONENT_SELECTOR">TinyDB1</field>
                    <value name="ARG0"><block type="text" id="b79"><field name="TEXT">fwd_count</field></block></value>
                    <value name="ARG1"><block type="math_number" id="b80"><field name="NUM">0</field></block></value>
                  </block>
                </value>
                <value name="B"><block type="math_number" id="b81"><field name="NUM">1</field></block></value>
              </block>

            </value>
            <next>
              <!-- Update count label -->
              <block type="component_set_get" id="b82">
                <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblCount"></mutation>
                <field name="COMPONENT_SELECTOR">LblCount</field>
                <field name="PROP">Text</field>
                <value name="VALUE">
                  <block type="text_join" id="b83">
                    <mutation items="2"></mutation>
                    <value name="ADD0"><block type="text" id="b84"><field name="TEXT">Forwarded: </field></block></value>
                    <value name="ADD1">
                      <block type="component_method" id="b85">
                        <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                        <field name="COMPONENT_SELECTOR">TinyDB1</field>
                        <value name="ARG0"><block type="text" id="b86"><field name="TEXT">fwd_count</field></block></value>
                        <value name="ARG1"><block type="math_number" id="b87"><field name="NUM">0</field></block></value>
                      </block>
                    </value>
                  </block>
                </value>
                <next>
                  <!-- Update last SMS label: success -->
                  <block type="component_set_get" id="b88">
                    <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblLastSms"></mutation>
                    <field name="COMPONENT_SELECTOR">LblLastSms</field>
                    <field name="PROP">Text</field>
                    <value name="VALUE"><block type="text" id="b89"><field name="TEXT">✅ SMS dashboard pe pahunch gaya!</field></block></value>
                  </block>
                </next>
              </block>
            </next>
          </block>
        </statement>

        <!-- ELSE: show error -->
        <statement name="ELSE">
          <block type="component_set_get" id="b90">
            <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblLastSms"></mutation>
            <field name="COMPONENT_SELECTOR">LblLastSms</field>
            <field name="PROP">Text</field>
            <value name="VALUE">
              <block type="text_join" id="b91">
                <mutation items="2"></mutation>
                <value name="ADD0"><block type="text" id="b92"><field name="TEXT">❌ Error: Code </field></block></value>
                <value name="ADD1"><block type="lexical_variable_get" id="b93"><field name="VAR">responseCode</field></block></value>
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
out = "SMSDashboard.aia"
src = "src/appinventor/ai_user/SMSDashboard"

with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as z:
    z.writestr("youngandroidproject/project.properties", PROJECT_PROPS)
    z.writestr(f"{src}/Screen1.scm", SCREEN_SCM)
    z.writestr(f"{src}/Screen1.bky", SCREEN_BKY)
    z.writestr("assets/.placeholder", "")

size = os.path.getsize(out)
print(f"Created: {out}  ({size:,} bytes)")

# Verify ZIP is valid
with zipfile.ZipFile(out, "r") as z:
    bad = z.testzip()
    if bad:
        print(f"CORRUPT FILE: {bad}")
    else:
        print("ZIP integrity: OK")
        for info in z.infolist():
            print(f"  {info.filename:60s} {info.file_size:,} bytes")
