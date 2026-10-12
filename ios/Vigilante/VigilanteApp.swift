import AVFoundation
import SwiftUI

/// L'app ne fait qu'emballer le jeu web (ios/www/, généré par composer build:ios) dans une vue plein écran.
@main
struct VigilanteApp: App {
    init() {
        // .playback : la musique de l'album joue même si l'iPhone est en mode silencieux.
        try? AVAudioSession.sharedInstance().setCategory(.playback, mode: .default)
    }

    var body: some Scene {
        WindowGroup {
            GameView()
                .ignoresSafeArea()
                .background(Color.black)
                .statusBarHidden()
                .persistentSystemOverlays(.hidden)
        }
    }
}
