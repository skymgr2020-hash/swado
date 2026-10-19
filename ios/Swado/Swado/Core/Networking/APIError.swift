import Foundation

enum APIError: Error, LocalizedError {
    case invalidURL
    case transport(Error)
    case httpStatus(Int, body: String?)
    case decoding(Error)

    var errorDescription: String? {
        switch self {
        case .invalidURL:
            return "Invalid request URL."
        case .transport(let error):
            return error.localizedDescription
        case .httpStatus(let code, let body):
            return "Server returned \(code)." + (body.map { " \($0)" } ?? "")
        case .decoding:
            return "Couldn't understand the server's response."
        }
    }
}
