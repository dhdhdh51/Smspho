package com.smsdashboard.viewer;

import android.annotation.SuppressLint;
import android.app.Activity;
import android.app.NotificationManager;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.os.Build;
import android.os.Bundle;
import android.webkit.JavascriptInterface;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

public class MainActivity extends Activity {

    private WebView webView;
    private SharedPreferences prefs;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        prefs   = getSharedPreferences("sms_viewer", MODE_PRIVATE);
        webView = findViewById(R.id.webView);

        setupWebView();
        requestNotifPermission();
        webView.loadUrl(Api.BASE + "/dashboard.php");
    }

    @SuppressLint("SetJavaScriptEnabled")
    private void setupWebView() {
        WebSettings s = webView.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setCacheMode(WebSettings.LOAD_DEFAULT);

        webView.addJavascriptInterface(new Bridge(), "Android");

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView v, WebResourceRequest req) {
                return false; // handle all URLs in WebView
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                // Extract api_key from the dashboard page hidden input
                view.evaluateJavascript(
                    "(function(){" +
                    "  var el = document.getElementById('apiKeyField');" +
                    "  if(el && el.value && el.value.length > 5) return el.value;" +
                    "  return '';" +
                    "})();",
                    value -> {
                        if (value == null) return;
                        String key = value.replace("\"", "").trim();
                        if (!key.isEmpty() && !key.equals("null")) {
                            prefs.edit().putString("api_key", key).apply();
                            startPollService();
                        }
                    }
                );
            }
        });
    }

    private class Bridge {
        @JavascriptInterface
        public void setApiKey(String key) {
            prefs.edit().putString("api_key", key).apply();
            runOnUiThread(() -> startPollService());
        }
    }

    private void startPollService() {
        if (prefs.getString("api_key", "").isEmpty()) return;
        startForegroundService(new Intent(this, PollService.class));
    }

    private void requestNotifPermission() {
        if (Build.VERSION.SDK_INT >= 33 &&
            checkSelfPermission("android.permission.POST_NOTIFICATIONS")
                != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(new String[]{"android.permission.POST_NOTIFICATIONS"}, 99);
        }
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) webView.goBack();
        else super.onBackPressed();
    }

    @Override
    protected void onDestroy() {
        webView.destroy();
        super.onDestroy();
    }
}
