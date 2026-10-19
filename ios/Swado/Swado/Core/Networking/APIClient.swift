import Foundation

/// Thin URLSession wrapper. No Alamofire — the backend's per-endpoint response
/// envelope irregularities and string-or-number price fields need custom
/// decoding regardless of library (see plan's networking rationale).
actor APIClient {
    private let session: URLSession
    private let baseURL: URL
    let decoder: JSONDecoder
    private let encoder: JSONEncoder

    init(session: URLSession = .shared) {
        self.session = session
        let configuredBaseURL = Bundle.main.object(forInfoDictionaryKey: "API_BASE_URL") as? String
        self.baseURL = URL(string: configuredBaseURL ?? "http://localhost:8000/api/")!

        let decoder = JSONDecoder()
        decoder.keyDecodingStrategy = .convertFromSnakeCase
        self.decoder = decoder
        self.encoder = JSONEncoder()
    }

    func get<T: Decodable>(_ path: String, as type: T.Type) async throws -> T {
        try await send(path: path, method: "GET", body: Optional<Data>.none)
    }

    func post<T: Decodable>(_ path: String, as type: T.Type) async throws -> T {
        try await send(path: path, method: "POST", body: Optional<Data>.none)
    }

    func post<Body: Encodable, T: Decodable>(_ path: String, body: Body, as type: T.Type) async throws -> T {
        let data: Data
        do {
            data = try encoder.encode(body)
        } catch {
            throw APIError.decoding(error)
        }
        return try await send(path: path, method: "POST", body: data)
    }

    private func send<T: Decodable>(path: String, method: String, body: Data?) async throws -> T {
        guard let url = URL(string: path, relativeTo: baseURL) else {
            throw APIError.invalidURL
        }
        var request = URLRequest(url: url, timeoutInterval: 25)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        if let token = KeychainStore.shared.token {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }
        if let body {
            request.httpBody = body
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        }

        let data: Data
        let response: URLResponse
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }

        guard let httpResponse = response as? HTTPURLResponse else {
            throw APIError.transport(URLError(.badServerResponse))
        }
        guard (200...299).contains(httpResponse.statusCode) else {
            throw APIError.httpStatus(httpResponse.statusCode, body: String(data: data, encoding: .utf8))
        }

        do {
            return try decoder.decode(T.self, from: data)
        } catch {
            throw APIError.decoding(error)
        }
    }
}
