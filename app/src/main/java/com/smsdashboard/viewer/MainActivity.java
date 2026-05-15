package com.smsdashboard.viewer;

import android.Manifest;
import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.os.Bundle;
import android.view.KeyEvent;
import android.view.View;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.Toast;

public class MainActivity extends Activity {

    private static final String DASHBOARD_URL = "https://sms.bharatseo.site";
    private WebView webView;
    private ProgressBar progressBar;
    private LinearLayout setupPanel;
    private EditText etApiKey;
    private SharedPreferences prefs;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        prefs       = getSharedPreferences("sms_viewer", MODE_PRIVATE);
        progressBar = findViewById(R.id.progressBar);
        webView     = findViewById(R.id.webView);
        setupPanel  = findViewById(R.id.setupPanel);
        etApiKey    = findViewById(R.id.etApiKey);
        Button btnSave = findViewById(R.id.btnSave);

        btnSave.setOnClickListener(v -> saveApiKey());

        // Show setup if no API key
        if (prefs.getString("api_key", "").isEmpty()) {
            setupPanel.setVisibility(View.VISIBLE);
            webView.setVisibility(View.GONE);
        } else {
            startService();
            loadDashboard(savedInstanceState);
        }

        requestPermissions(new String[]{
            Manifest.permission.POST_NOTIFICATIONS
        }, 1);
    }

    private void saveApiKey() {
        String key = etApiKey.getText().toString().trim();
        if (key.isEmpty()) { Toast.makeText(this, "API Key daalo!", Toast.LENGTH_SHORT).show(); return; }
        prefs.edit().putString("api_key", key).apply();
        setupPanel.setVisibility(View.GONE);
        webView.setVisibility(View.VISIBLE);
        startService();
        loadDashboard(null);
    }

    private void startService() {
        startService(new Intent(this, PollService.class));
    }

    private void loadDashboard(Bundle saved) {
        WebSettings ws = webView.getSettings();
        ws.setJavaScriptEnabled(true);
        ws.setDomStorageEnabled(true);
        ws.setCacheMode(WebSettings.LOAD_DEFAULT);

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView v, WebResourceRequest r) {
                String url = r.getUrl().toString();
                if (url.startsWith(DASHBOARD_URL)) { v.loadUrl(url); return true; }
                return false;
            }
            @Override public void onPageStarted(WebView v, String u, Bitmap b) { progressBar.setVisibility(View.VISIBLE); }
            @Override public void onPageFinished(WebView v, String u) { progressBar.setVisibility(View.GONE); }
        });

        webView.setWebChromeClient(new WebChromeClient() {
            @Override public void onProgressChanged(WebView v, int p) { progressBar.setProgress(p); }
        });

        if (saved != null) webView.restoreState(saved);
        else webView.loadUrl(DASHBOARD_URL);
    }

    @Override
    public boolean onKeyDown(int keyCode, KeyEvent event) {
        if (keyCode == KeyEvent.KEYCODE_BACK && webView.canGoBack()) { webView.goBack(); return true; }
        return super.onKeyDown(keyCode, event);
    }

    @Override
    protected void onSaveInstanceState(Bundle out) {
        super.onSaveInstanceState(out);
        webView.saveState(out);
    }
}
