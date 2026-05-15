"""
Generates SMSDashboard.aia for MIT App Inventor.
Domain: sms.bharatseo.site
"""
import zipfile, os, textwrap

# ── File contents ──────────────────────────────────────────────────────────

PROJECT_PROPS = textwrap.dedent("""\
    main=appinventor.ai_user.SMSDashboard.Screen1
    name=SMSDashboard
    assets=../assets
    source=../src
    build=../build
    versioncode=1
    versionname=1.0
    useslocation=False
    aname=SMS Dashboard
""")

SCREEN_SCM = r"""#|
$JSON
{"YaVersion":"221","Source":"Form","Properties":{"$Name":"Screen1","$Type":"Form","$Version":"29","AppName":"SMS Dashboard","BackgroundColor":"&HFF0F172A","PrimaryColor":"&HFF3B82F6","PrimaryColorDark":"&HFF1E40AF","AccentColor":"&HFF60A5FA","Theme":"AppTheme.Dark.DarkActionBar","Sizing":"Responsive","ShowStatusBar":"True","Title":"SMS Dashboard","TitleVisible":"True","$Components":[{"$Name":"Texting1","$Type":"Texting","$Version":"2","ReceivingEnabled":"2"},{"$Name":"Web1","$Type":"Web","$Version":"6","Url":"https://sms.bharatseo.site/api/receive.php"},{"$Name":"TinyDB1","$Type":"TinyDB","$Version":"2","Namespace":"SMSDashboard"},{"$Name":"Notifier1","$Type":"Notifier","$Version":"3"},{"$Name":"ColMain","$Type":"VerticalArrangement","$Version":"4","AlignHorizontal":"3","BackgroundColor":"&HFF1E293B","Height":"-2","Width":"-2","$Components":[{"$Name":"LblTitle","$Type":"Label","$Version":"5","FontBold":"True","FontSize":"20","Text":"📱 SMS Dashboard","TextColor":"&HFFFFFFFF","Width":"-2"},{"$Name":"LblSubtitle","$Type":"Label","$Version":"5","FontSize":"12","Text":"Auto-forwarding to bharatseo.site","TextColor":"&HFF94A3B8","Width":"-2"}]},{"$Name":"ColDivider1","$Type":"HorizontalArrangement","$Version":"4","BackgroundColor":"&HFF334155","Height":"2","Width":"-2"},{"$Name":"ColSettings","$Type":"VerticalArrangement","$Version":"4","BackgroundColor":"&HFF1E293B","Height":"-2","Width":"-2","$Components":[{"$Name":"LblApiKeyTitle","$Type":"Label","$Version":"5","FontBold":"True","FontSize":"13","Text":"🔑 API Key","TextColor":"&HFFFFFFFF"},{"$Name":"LblApiKeyHint","$Type":"Label","$Version":"5","FontSize":"11","Text":"Dashboard se copy karo → API Settings","TextColor":"&HFF64748B"},{"$Name":"TxtApiKey","$Type":"TextBox","$Version":"6","BackgroundColor":"&HFF0F172A","FontSize":"12","Hint":"Apna API key yahan paste karo","TextColor":"&HFFFFFFFF","Width":"-2"},{"$Name":"BtnSave","$Type":"Button","$Version":"7","BackgroundColor":"&HFF3B82F6","FontBold":"True","Text":"💾 Save API Key","TextColor":"&HFFFFFFFF","Width":"-2"},{"$Name":"LblWebhook","$Type":"Label","$Version":"5","FontSize":"11","Text":"Webhook: https://sms.bharatseo.site/api/receive.php","TextColor":"&HFF64748B","Width":"-2"}]},{"$Name":"ColDivider2","$Type":"HorizontalArrangement","$Version":"4","BackgroundColor":"&HFF334155","Height":"2","Width":"-2"},{"$Name":"ColStatus","$Type":"VerticalArrangement","$Version":"4","BackgroundColor":"&HFF1E293B","Height":"-2","Width":"-2","$Components":[{"$Name":"LblStatusTitle","$Type":"Label","$Version":"5","FontBold":"True","FontSize":"13","Text":"📊 Status","TextColor":"&HFFFFFFFF"},{"$Name":"LblLive","$Type":"Label","$Version":"5","FontSize":"12","Text":"🟢 SMS Listening... Active","TextColor":"&HFF22C55E","Width":"-2"},{"$Name":"LblLastSms","$Type":"Label","$Version":"5","FontSize":"11","Text":"Last SMS: Koi message nahi mila abhi","TextColor":"&HFF94A3B8","Width":"-2"},{"$Name":"LblCount","$Type":"Label","$Version":"5","FontSize":"11","Text":"Forwarded: 0 messages","TextColor":"&HFF94A3B8","Width":"-2"}]}]}}
$JSON
|#
"""

SCREEN_BKY = """<xml xmlns="http://www.w3.org/1999/xhtml">
  <block type="component_event" id="init_block" x="20" y="20">
    <mutation component_type="Form" event_name="Initialize" is_generic="false" instance_name="Screen1"></mutation>
    <field name="COMPONENT_SELECTOR">Screen1</field>
    <statement name="DO">
      <block type="component_set_get" id="load_key_block">
        <mutation component_type="TextBox" set_or_get="set" property_name="Text" is_generic="false" instance_name="TxtApiKey"></mutation>
        <field name="COMPONENT_SELECTOR">TxtApiKey</field>
        <field name="PROP">Text</field>
        <value name="VALUE">
          <block type="component_method" id="tinydb_get">
            <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
            <field name="COMPONENT_SELECTOR">TinyDB1</field>
            <value name="ARG0"><block type="text" id="key_tag"><field name="TEXT">api_key</field></block></value>
            <value name="ARG1"><block type="text" id="key_default"><field name="TEXT"></field></block></value>
          </block>
        </value>
        <next>
          <block type="component_set_get" id="load_count">
            <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblCount"></mutation>
            <field name="COMPONENT_SELECTOR">LblCount</field>
            <field name="PROP">Text</field>
            <value name="VALUE">
              <block type="text_join" id="count_join">
                <mutation items="2"></mutation>
                <value name="ADD0"><block type="text" id="count_prefix"><field name="TEXT">Forwarded: </field></block></value>
                <value name="ADD1">
                  <block type="component_method" id="tinydb_count">
                    <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                    <field name="COMPONENT_SELECTOR">TinyDB1</field>
                    <value name="ARG0"><block type="text" id="count_tag"><field name="TEXT">fwd_count</field></block></value>
                    <value name="ARG1"><block type="math_number" id="count_def"><field name="NUM">0</field></block></value>
                  </block>
                </value>
              </block>
            </value>
          </block>
        </next>
      </block>
    </statement>
  </block>

  <block type="component_event" id="save_btn_block" x="20" y="320">
    <mutation component_type="Button" event_name="Click" is_generic="false" instance_name="BtnSave"></mutation>
    <field name="COMPONENT_SELECTOR">BtnSave</field>
    <statement name="DO">
      <block type="component_method" id="save_key">
        <mutation component_type="TinyDB" method_name="StoreValue" is_generic="false" instance_name="TinyDB1"></mutation>
        <field name="COMPONENT_SELECTOR">TinyDB1</field>
        <value name="ARG0"><block type="text" id="save_tag"><field name="TEXT">api_key</field></block></value>
        <value name="ARG1">
          <block type="component_set_get" id="get_textbox_val">
            <mutation component_type="TextBox" set_or_get="get" property_name="Text" is_generic="false" instance_name="TxtApiKey"></mutation>
            <field name="COMPONENT_SELECTOR">TxtApiKey</field>
            <field name="PROP">Text</field>
          </block>
        </value>
        <next>
          <block type="component_method" id="show_saved_toast">
            <mutation component_type="Notifier" method_name="ShowToast" is_generic="false" instance_name="Notifier1"></mutation>
            <field name="COMPONENT_SELECTOR">Notifier1</field>
            <value name="ARG0"><block type="text" id="saved_msg"><field name="TEXT">✅ API Key save ho gaya!</field></block></value>
          </block>
        </next>
      </block>
    </statement>
  </block>

  <block type="component_event" id="sms_received_block" x="400" y="20">
    <mutation component_type="Texting" event_name="MessageReceived" is_generic="false" instance_name="Texting1"></mutation>
    <field name="COMPONENT_SELECTOR">Texting1</field>
    <statement name="DO">
      <block type="local_declaration_statement" id="declare_api_key">
        <mutation>
          <localname name="savedApiKey"></localname>
        </mutation>
        <value name="DECL0">
          <block type="component_method" id="get_saved_key">
            <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
            <field name="COMPONENT_SELECTOR">TinyDB1</field>
            <value name="ARG0"><block type="text" id="get_key_tag"><field name="TEXT">api_key</field></block></value>
            <value name="ARG1"><block type="text" id="get_key_default"><field name="TEXT"></field></block></value>
          </block>
        </value>
        <statement name="STACK">
          <block type="controls_if" id="check_api_key">
            <value name="IF0">
              <block type="text_isEmpty" id="check_empty">
                <value name="VALUE">
                  <block type="lexical_variable_get" id="get_api_key_var">
                    <field name="VAR">savedApiKey</field>
                  </block>
                </value>
              </block>
            </value>
            <statement name="DO0">
              <block type="component_method" id="show_no_key_toast">
                <mutation component_type="Notifier" method_name="ShowToast" is_generic="false" instance_name="Notifier1"></mutation>
                <field name="COMPONENT_SELECTOR">Notifier1</field>
                <value name="ARG0"><block type="text" id="no_key_msg"><field name="TEXT">⚠️ Pehle API Key save karo!</field></block></value>
              </block>
            </statement>
            <statement name="ELSE">
              <block type="component_set_get" id="set_headers">
                <mutation component_type="Web" set_or_get="set" property_name="RequestHeaders" is_generic="false" instance_name="Web1"></mutation>
                <field name="COMPONENT_SELECTOR">Web1</field>
                <field name="PROP">RequestHeaders</field>
                <value name="VALUE">
                  <block type="lists_create_with" id="headers_list">
                    <mutation items="1"></mutation>
                    <value name="ADD0">
                      <block type="lists_create_with" id="ct_pair">
                        <mutation items="2"></mutation>
                        <value name="ADD0"><block type="text" id="ct_key"><field name="TEXT">Content-Type</field></block></value>
                        <value name="ADD1"><block type="text" id="ct_val"><field name="TEXT">application/json</field></block></value>
                      </block>
                    </value>
                  </block>
                </value>
                <next>
                  <block type="component_method" id="post_to_server">
                    <mutation component_type="Web" method_name="PostText" is_generic="false" instance_name="Web1"></mutation>
                    <field name="COMPONENT_SELECTOR">Web1</field>
                    <value name="ARG0">
                      <block type="text_join" id="build_json">
                        <mutation items="9"></mutation>
                        <value name="ADD0"><block type="text" id="j1"><field name="TEXT">{"api_key":"</field></block></value>
                        <value name="ADD1">
                          <block type="lexical_variable_get" id="j_apikey">
                            <field name="VAR">savedApiKey</field>
                          </block>
                        </value>
                        <value name="ADD2"><block type="text" id="j2"><field name="TEXT">","sender":"</field></block></value>
                        <value name="ADD3">
                          <block type="lexical_variable_get" id="j_sender">
                            <field name="VAR">number</field>
                          </block>
                        </value>
                        <value name="ADD4"><block type="text" id="j3"><field name="TEXT">","message":"</field></block></value>
                        <value name="ADD5">
                          <block type="lexical_variable_get" id="j_message">
                            <field name="VAR">messageText</field>
                          </block>
                        </value>
                        <value name="ADD6"><block type="text" id="j4"><field name="TEXT">","device":"</field></block></value>
                        <value name="ADD7"><block type="text" id="j5"><field name="TEXT">Android Phone</field></block></value>
                        <value name="ADD8"><block type="text" id="j6"><field name="TEXT">"}</field></block></value>
                      </block>
                    </value>
                    <next>
                      <block type="component_set_get" id="update_last_sms">
                        <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblLastSms"></mutation>
                        <field name="COMPONENT_SELECTOR">LblLastSms</field>
                        <field name="PROP">Text</field>
                        <value name="VALUE">
                          <block type="text_join" id="last_sms_join">
                            <mutation items="3"></mutation>
                            <value name="ADD0"><block type="text" id="ls1"><field name="TEXT">Last: </field></block></value>
                            <value name="ADD1">
                              <block type="lexical_variable_get" id="ls_sender">
                                <field name="VAR">number</field>
                              </block>
                            </value>
                            <value name="ADD2"><block type="text" id="ls2"><field name="TEXT"> → Forwarded ✅</field></block></value>
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
    </statement>
  </block>

  <block type="component_event" id="web_got_text" x="400" y="580">
    <mutation component_type="Web" event_name="GotText" is_generic="false" instance_name="Web1"></mutation>
    <field name="COMPONENT_SELECTOR">Web1</field>
    <statement name="DO">
      <block type="controls_if" id="check_response">
        <value name="IF0">
          <block type="logic_compare" id="cmp_200">
            <field name="OP">EQ</field>
            <value name="A">
              <block type="lexical_variable_get" id="resp_code">
                <field name="VAR">responseCode</field>
              </block>
            </value>
            <value name="B"><block type="math_number" id="n200"><field name="NUM">200</field></block></value>
          </block>
        </value>
        <statement name="DO0">
          <block type="local_declaration_statement" id="declare_new_count">
            <mutation>
              <localname name="newCount"></localname>
            </mutation>
            <value name="DECL0">
              <block type="math_add" id="inc_count">
                <value name="NUM0">
                  <block type="component_method" id="get_old_count">
                    <mutation component_type="TinyDB" method_name="GetValue" is_generic="false" instance_name="TinyDB1"></mutation>
                    <field name="COMPONENT_SELECTOR">TinyDB1</field>
                    <value name="ARG0"><block type="text" id="cnt_tag2"><field name="TEXT">fwd_count</field></block></value>
                    <value name="ARG1"><block type="math_number" id="cnt_def2"><field name="NUM">0</field></block></value>
                  </block>
                </value>
                <value name="NUM1"><block type="math_number" id="one"><field name="NUM">1</field></block></value>
              </block>
            </value>
            <statement name="STACK">
              <block type="component_method" id="save_new_count">
                <mutation component_type="TinyDB" method_name="StoreValue" is_generic="false" instance_name="TinyDB1"></mutation>
                <field name="COMPONENT_SELECTOR">TinyDB1</field>
                <value name="ARG0"><block type="text" id="cnt_store_tag"><field name="TEXT">fwd_count</field></block></value>
                <value name="ARG1">
                  <block type="lexical_variable_get" id="get_new_count">
                    <field name="VAR">newCount</field>
                  </block>
                </value>
                <next>
                  <block type="component_set_get" id="update_count_lbl">
                    <mutation component_type="Label" set_or_get="set" property_name="Text" is_generic="false" instance_name="LblCount"></mutation>
                    <field name="COMPONENT_SELECTOR">LblCount</field>
                    <field name="PROP">Text</field>
                    <value name="VALUE">
                      <block type="text_join" id="count_update_join">
                        <mutation items="2"></mutation>
                        <value name="ADD0"><block type="text" id="cu1"><field name="TEXT">Forwarded: </field></block></value>
                        <value name="ADD1">
                          <block type="lexical_variable_get" id="get_nc2">
                            <field name="VAR">newCount</field>
                          </block>
                        </value>
                      </block>
                    </value>
                  </block>
                </next>
              </block>
            </statement>
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
    z.writestr("assets/.placeholder", "")   # assets dir placeholder

print(f"✅ Created: {out}  ({os.path.getsize(out):,} bytes)")
print("📱 MIT App Inventor mein import karo → Build → App (APK)")
