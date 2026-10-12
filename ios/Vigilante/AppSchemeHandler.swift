import UniformTypeIdentifiers
import WebKit

/// Sert les fichiers de www/ sous app://localhost/… : en file://, WebKit bloque les modules ES
/// et les fetch() des décors et des musiques. Le ?v=… des URL (cache du web) est ignoré.
final class AppSchemeHandler: NSObject, WKURLSchemeHandler {
    static let scheme = "app"

    private let root = Bundle.main.resourceURL!.appendingPathComponent("www", isDirectory: true).standardizedFileURL

    func webView(_ webView: WKWebView, start task: any WKURLSchemeTask) {
        guard let url = task.request.url, let file = file(for: url), let data = try? Data(contentsOf: file) else {
            task.didReceive(HTTPURLResponse(url: task.request.url ?? GameView.start, statusCode: 404, httpVersion: "HTTP/1.1", headerFields: nil)!)
            task.didFinish()
            return
        }
        let headers = ["Content-Type": mimeType(of: file), "Content-Length": String(data.count)]
        task.didReceive(HTTPURLResponse(url: url, statusCode: 200, httpVersion: "HTTP/1.1", headerFields: headers)!)
        task.didReceive(data)
        task.didFinish()
    }

    func webView(_ webView: WKWebView, stop task: any WKURLSchemeTask) {
        // Les réponses partent d'un bloc dans start : rien à interrompre.
    }

    /// Le fichier demandé, à condition qu'il soit bien dans www/.
    private func file(for url: URL) -> URL? {
        let path = url.path.isEmpty || url.path == "/" ? "/index.html" : url.path
        let file = root.appendingPathComponent(path).standardizedFileURL
        return file.path.hasPrefix(root.path + "/") ? file : nil
    }

    private func mimeType(of file: URL) -> String {
        if file.pathExtension == "js" { return "text/javascript" } // obligatoire pour les modules ES
        return UTType(filenameExtension: file.pathExtension)?.preferredMIMEType ?? "application/octet-stream"
    }
}
