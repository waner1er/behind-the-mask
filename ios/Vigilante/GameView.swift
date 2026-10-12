import SwiftUI
import WebKit

/// La borne : une WKWebView qui charge le jeu embarqué, servi par AppSchemeHandler.
struct GameView: UIViewRepresentable {
    static let start = URL(string: "\(AppSchemeHandler.scheme)://localhost/index.html")!

    func makeUIView(context: Context) -> GameWebView {
        let configuration = WKWebViewConfiguration()
        configuration.setURLSchemeHandler(AppSchemeHandler(), forURLScheme: AppSchemeHandler.scheme)
        configuration.userContentController.add(HapticsHandler(), name: HapticsHandler.name)
        configuration.allowsInlineMediaPlayback = true
        configuration.mediaTypesRequiringUserActionForPlayback = []

        let webView = GameWebView(frame: .zero, configuration: configuration)
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

    func updateUIView(_ webView: GameWebView, context: Context) {}
}

/// Sur iOS, WebKit ne transmet les manettes (et le clavier physique) qu'à la vue web qui a
/// le focus de la fenêtre (UIGamepadProviderIOS.mm : le first responder de la keyWindow).
/// Safari le donne à sa page ; ici personne ne le ferait : on le prend et on le reprend.
final class GameWebView: WKWebView {
    private var activation: NSObjectProtocol?

    override func didMoveToWindow() {
        super.didMoveToWindow()
        guard window != nil else { return }
        takeFocus()
        activation = activation ?? NotificationCenter.default.addObserver(
            forName: UIApplication.didBecomeActiveNotification, object: nil, queue: .main
        ) { [weak self] _ in
            MainActor.assumeIsolated { self?.takeFocus() }
        }
    }

    /// Après la mise en page : un first responder demandé trop tôt est ignoré.
    private func takeFocus() {
        DispatchQueue.main.async { [weak self] in
            self?.window?.makeKey()
            _ = self?.becomeFirstResponder()
        }
    }
}
