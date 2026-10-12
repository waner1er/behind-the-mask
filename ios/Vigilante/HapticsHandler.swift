import UIKit
import WebKit

/// Reçoit les vibrations du jeu (js/input/Haptics.js : window.webkit.messageHandlers.haptics)
/// et les joue avec le moteur haptique de l'iPhone. Respecte le réglage « Vibrations » du système.
@MainActor
final class HapticsHandler: NSObject, WKScriptMessageHandler {
    static let name = "haptics"

    private let impacts: [String: UIImpactFeedbackGenerator] = [
        "light": UIImpactFeedbackGenerator(style: .light),
        "medium": UIImpactFeedbackGenerator(style: .medium),
        "heavy": UIImpactFeedbackGenerator(style: .heavy),
    ]
    private let notification = UINotificationFeedbackGenerator()

    func userContentController(_ controller: WKUserContentController, didReceive message: WKScriptMessage) {
        switch message.body as? String {
        case "success":
            notification.notificationOccurred(.success)
        case "error":
            notification.notificationOccurred(.error)
        case let style?:
            impacts[style]?.impactOccurred()
        default:
            break
        }
    }
}
