import SwiftUI
import WebKit

/// La borne : une WKWebView qui charge le jeu embarqué, servi par AppSchemeHandler.
struct GameView: UIViewRepresentable {
    static let start = URL(string: "\(AppSchemeHandler.scheme)://localhost/index.html")!

    func makeUIView(context: Context) -> WKWebView {
        let configuration = WKWebViewConfiguration()
        configuration.setURLSchemeHandler(AppSchemeHandler(), forURLScheme: AppSchemeHandler.scheme)
        configuration.allowsInlineMediaPlayback = true
        configuration.mediaTypesRequiringUserActionForPlayback = []

        let webView = WKWebView(frame: .zero, configuration: configuration)
        webView.isOpaque = false
        webView.backgroundColor = .black
        // Le jeu gère lui-même le doigt (MobileGuard.js) : pas de défilement ni de rebond.
        webView.scrollView.isScrollEnabled = false
        webView.scrollView.bounces = false
        webView.scrollView.contentInsetAdjustmentBehavior = .never
        #if DEBUG
        webView.isInspectable = true // Safari › Développement › Simulateur
        #endif
        webView.load(URLRequest(url: Self.start))
        return webView
    }

    func updateUIView(_ webView: WKWebView, context: Context) {}
}
