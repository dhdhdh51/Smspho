package com.smsdashboard.viewer;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

class Api {
    static final String BASE = "https://sms.bharatseo.site";

    static String post(String path, String jsonBody) throws Exception {
        URL url = new URL(BASE + path);
        HttpURLConnection c = (HttpURLConnection) url.openConnection();
        c.setRequestMethod("POST");
        c.setRequestProperty("Content-Type", "application/json");
        c.setConnectTimeout(15000);
        c.setReadTimeout(15000);
        c.setDoOutput(true);
        try (OutputStream os = c.getOutputStream()) {
            os.write(jsonBody.getBytes(StandardCharsets.UTF_8));
        }
        return read(c);
    }

    static String get(String path) throws Exception {
        URL url = new URL(BASE + path);
        HttpURLConnection c = (HttpURLConnection) url.openConnection();
        c.setRequestMethod("GET");
        c.setConnectTimeout(15000);
        c.setReadTimeout(15000);
        return read(c);
    }

    private static String read(HttpURLConnection c) throws Exception {
        boolean ok = c.getResponseCode() < 400;
        BufferedReader br = new BufferedReader(new InputStreamReader(
            ok ? c.getInputStream() : c.getErrorStream(), StandardCharsets.UTF_8));
        StringBuilder sb = new StringBuilder();
        String line;
        while ((line = br.readLine()) != null) sb.append(line);
        br.close();
        c.disconnect();
        if (!ok) throw new Exception(sb.toString());
        return sb.toString();
    }

    // Tiny JSON helpers — no library needed
    static String str(String json, String key) {
        String search = "\"" + key + "\":\"";
        int i = json.indexOf(search);
        if (i < 0) return "";
        int s = i + search.length();
        int e = json.indexOf("\"", s);
        return e < 0 ? "" : json.substring(s, e)
            .replace("\\n", "\n").replace("\\\"", "\"").replace("\\/", "/");
    }

    static int num(String json, String key) {
        String search = "\"" + key + "\":";
        int i = json.indexOf(search);
        if (i < 0) return 0;
        int s = i + search.length();
        int e = s;
        while (e < json.length() && (Character.isDigit(json.charAt(e)) || json.charAt(e) == '-')) e++;
        try { return Integer.parseInt(json.substring(s, e)); } catch (Exception ex) { return 0; }
    }
}
