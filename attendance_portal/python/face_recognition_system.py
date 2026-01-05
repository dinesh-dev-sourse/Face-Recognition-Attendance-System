import cv2
import mediapipe as mp
import numpy as np
import mysql.connector
import pickle
import json
from datetime import datetime
import requests

class FaceRecognitionAttendance:
    def __init__(self, db_config):
        """Initialize face recognition system"""
        self.db_config = db_config
        self.mp_face_detection = mp.solutions.face_detection
        self.mp_face_mesh = mp.solutions.face_mesh
        self.face_detection = self.mp_face_detection.FaceDetection(
            model_selection=1, 
            min_detection_confidence=0.7
        )
        self.face_mesh = self.mp_face_mesh.FaceMesh(
            static_image_mode=False,
            max_num_faces=1,
            min_detection_confidence=0.7,
            min_tracking_confidence=0.7
        )
        self.known_faces = {}
        self.load_known_faces()
    
    def connect_db(self):
        """Connect to MySQL database"""
        return mysql.connector.connect(
            host=self.db_config['host'],
            port=self.db_config['port'],
            user=self.db_config['user'],
            password=self.db_config['password'],
            database=self.db_config['database']
        )
    
    def load_known_faces(self):
        """Load face encodings from database"""
        try:
            conn = self.connect_db()
            cursor = conn.cursor(dictionary=True)
            cursor.execute("""
                SELECT user_id, full_name, face_encoding 
                FROM users 
                WHERE face_encoding IS NOT NULL AND is_active = 1
            """)
            
            for row in cursor.fetchall():
                if row['face_encoding']:
                    self.known_faces[row['user_id']] = {
                        'name': row['full_name'],
                        'encoding': pickle.loads(row['face_encoding'].encode('latin1'))
                    }
            
            cursor.close()
            conn.close()
            print(f"Loaded {len(self.known_faces)} face encodings")
        except Exception as e:
            print(f"Error loading faces: {e}")
    
    def extract_face_features(self, image):
        """Extract facial features using MediaPipe"""
        rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        results = self.face_mesh.process(rgb_image)
        
        if not results.multi_face_landmarks:
            return None
        
        # Extract landmark coordinates
        landmarks = results.multi_face_landmarks[0]
        features = []
        
        for landmark in landmarks.landmark:
            features.extend([landmark.x, landmark.y, landmark.z])
        
        return np.array(features)
    
    def register_face(self, user_id, image_path=None, video_capture=None):
        """Register a new face for a user"""
        try:
            if image_path:
                image = cv2.imread(image_path)
            elif video_capture:
                ret, image = video_capture.read()
                if not ret:
                    return {'success': False, 'error': 'Failed to capture image'}
            else:
                return {'success': False, 'error': 'No image source provided'}
            
            # Extract features
            features = self.extract_face_features(image)
            if features is None:
                return {'success': False, 'error': 'No face detected'}
            
            # Store in database
            conn = self.connect_db()
            cursor = conn.cursor()
            
            face_encoding_blob = pickle.dumps(features).decode('latin1')
            cursor.execute("""
                UPDATE users 
                SET face_encoding = %s 
                WHERE user_id = %s
            """, (face_encoding_blob, user_id))
            
            conn.commit()
            cursor.close()
            conn.close()
            
            # Reload known faces
            self.load_known_faces()
            
            return {'success': True, 'message': 'Face registered successfully'}
        
        except Exception as e:
            return {'success': False, 'error': str(e)}
    
    def recognize_face(self, image):
        """Recognize face from image"""
        features = self.extract_face_features(image)
        
        if features is None:
            return None, 0.0
        
        # Compare with known faces
        best_match_id = None
        best_confidence = 0.0
        threshold = 0.75
        
        for user_id, data in self.known_faces.items():
            # Calculate cosine similarity
            similarity = np.dot(features, data['encoding']) / (
                np.linalg.norm(features) * np.linalg.norm(data['encoding'])
            )
            
            # Convert to confidence percentage
            confidence = (similarity + 1) / 2 * 100
            
            if confidence > best_confidence and confidence >= threshold * 100:
                best_confidence = confidence
                best_match_id = user_id
        
        return best_match_id, best_confidence
    
    def mark_attendance_api(self, user_id, confidence, csrf_token):
        """Mark attendance via API"""
        try:
            response = requests.post(
                'http://localhost/attendance_portal/api/mark_attendance.php',
                json={
                    'user_id': user_id,
                    'recognition_confidence': confidence,
                    'csrf_token': csrf_token
                },
                headers={'Content-Type': 'application/json'}
            )
            return response.json()
        except Exception as e:
            return {'success': False, 'error': str(e)}
    
    def start_attendance_capture(self, csrf_token):
        """Start video capture for attendance"""
        cap = cv2.VideoCapture(0)
        
        print("Face Recognition Attendance System")
        print("Press 'SPACE' to capture and mark attendance")
        print("Press 'Q' to quit")
        
        while True:
            ret, frame = cap.read()
            if not ret:
                break
            
            # Detect faces
            rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
            detection_results = self.face_detection.process(rgb_frame)
            
            # Draw face detection boxes
            if detection_results.detections:
                for detection in detection_results.detections:
                    bboxC = detection.location_data.relative_bounding_box
                    h, w, _ = frame.shape
                    x = int(bboxC.xmin * w)
                    y = int(bboxC.ymin * h)
                    width = int(bboxC.width * w)
                    height = int(bboxC.height * h)
                    
                    cv2.rectangle(frame, (x, y), (x + width, y + height), (0, 255, 0), 2)
                    cv2.putText(frame, 'Face Detected', (x, y - 10), 
                              cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 2)
            
            # Display instructions
            cv2.putText(frame, 'Press SPACE to mark attendance', (10, 30), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
            cv2.putText(frame, 'Press Q to quit', (10, 60), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
            
            cv2.imshow('Attendance System', frame)
            
            key = cv2.waitKey(1) & 0xFF
            
            if key == ord(' '):  # Space bar
                user_id, confidence = self.recognize_face(frame)
                
                if user_id:
                    name = self.known_faces[user_id]['name']
                    print(f"\nRecognized: {name} (Confidence: {confidence:.2f}%)")
                    
                    # Mark attendance
                    result = self.mark_attendance_api(user_id, confidence, csrf_token)
                    
                    if result.get('success'):
                        print(f"✓ Attendance marked successfully!")
                        print(f"  Time: {result['data']['check_in_time']}")
                        print(f"  Status: {result['data']['status'].upper()}")
                    else:
                        print(f"✗ Error: {result.get('error', 'Unknown error')}")
                else:
                    print("\n✗ Face not recognized. Please try again.")
                
                cv2.waitKey(2000)  # Wait 2 seconds
            
            elif key == ord('q'):
                break
        
        cap.release()
        cv2.destroyAllWindows()


# Configuration
db_config = {
    'host': 'localhost',
    'port': 3307,
    'user': 'root',
    'password': '',
    'database': 'attendance_portal'
}

if __name__ == "__main__":
    system = FaceRecognitionAttendance(db_config)
    
    print("\n=== Face Recognition Attendance System ===")
    print("1. Register new face")
    print("2. Start attendance capture")
    print("3. Exit")
    
    choice = input("\nEnter your choice: ")
    
    if choice == '1':
        user_id = input("Enter user ID: ")
        print("Position your face in front of the camera...")
        cap = cv2.VideoCapture(0)
        result = system.register_face(user_id, video_capture=cap)
        cap.release()
        print(result)
    
    elif choice == '2':
        csrf_token = input("Enter CSRF token from your session: ")
        system.start_attendance_capture(csrf_token)
    
    else:
        print("Exiting...")